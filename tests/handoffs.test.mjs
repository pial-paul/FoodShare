import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { mkdtempSync, readFileSync, rmSync } from 'node:fs';
import { createServer } from 'node:net';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { setTimeout as delay } from 'node:timers/promises';
import test from 'node:test';

const password = randomBytes(18).toString('hex');
function php(code, data = {}) {
  const run = spawnSync('php', [], {
    input: '<?php require "config/config.php"; ' + code,
    encoding: 'utf8', env: { ...process.env, FIXTURE: JSON.stringify(data) },
  });
  assert.equal(run.status, 0, run.stderr || run.stdout);
  return run.stdout.trim();
}
function query(sql, args = []) {
  return JSON.parse(php('[$sql,$args]=json_decode(getenv("FIXTURE"),true); $result=db()->execute_query($sql,$args); echo json_encode($result instanceof mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : []);', [sql,args]));
}
function script(file) {
  return spawnSync('php', [file], { encoding: 'utf8' });
}
function schema(file) {
  php('db()->multi_query(file_get_contents(json_decode(getenv("FIXTURE"),true))); while(db()->more_results()) { db()->next_result(); }', file);
}
async function serve(t, storage) {
  const socket = createServer();
  await new Promise(resolve => socket.listen(0, '127.0.0.1', resolve));
  const port = socket.address().port;
  await new Promise(resolve => socket.close(resolve));
  const child = spawn('php', ['-d', 'session.save_path=' + storage, '-d', 'upload_max_filesize=5M',
    '-S', '127.0.0.1:' + port, '-t', 'public'], {
    env: { ...process.env, FOODSHARE_UPLOAD_DIR: storage, FOODSHARE_TIMEZONE: 'UTC' },
    stdio: 'ignore', windowsHide: true,
  });
  t.after(async () => {
    if (child.exitCode === null && child.signalCode === null) {
      await new Promise(resolve => { child.once('exit', resolve); child.kill(); });
    }
  });
  const base = 'http://127.0.0.1:' + port + '/';
  for (let i=0; i<60; i++) {
    try { if ((await fetch(base)).ok) return base; } catch {}
    await delay(100);
  }
  throw new Error('Test HTTP server did not start.');
}
function browser(base) {
  let cookie = '', csrf = '';
  async function send(path, body) {
    const res = await fetch(base + path, { method: body ? 'POST' : 'GET', body,
      headers: { Cookie: cookie }, redirect: 'manual' });
    cookie = res.headers.get('set-cookie')?.split(';')[0] || cookie;
    const text = await res.text();
    csrf = text.match(/name="csrf" value="([a-f0-9]+)"/)?.[1] || csrf;
    assert.ok(res.status < 500, path + ' returned ' + res.status);
    return { status: res.status, text, headers: res.headers };
  }
  return {
    get: path => send(path),
    post: (path, fields) => send(path, new URLSearchParams({ csrf, ...fields })),
    upload: (fields, bytes) => {
      const form = new FormData();
      for (const [key,value] of Object.entries({ csrf, ...fields })) form.set(key,value);
      form.set('image', new Blob([bytes], { type: 'image/png' }), 'donation.png');
      return send('donate.php',form);
    },
    cookie: () => cookie,
  };
}

test('original schema upgrade and donation lifecycle through HTTP', async t => {
  assert.match(process.env.FOODSHARE_DB_NAME || '', /_test$/, 'Use a new empty database ending in _test');
  assert.equal(query('SHOW TABLES').length, 0, 'Test suite requires an empty database');
  schema('sql/foodshare_db.sql');
  query("INSERT INTO users (name,email,password,role) VALUES ('Legacy','legacy@example.invalid',?,'donor')",
    [php('echo password_hash(json_decode(getenv("FIXTURE"),true),PASSWORD_DEFAULT);', password)]);
  query("INSERT INTO donations (donor_id,food_name,quantity,location,expiry_date,status) VALUES (1,'Legacy loaf','1 loaf','North','2099-01-01','requested')");
  assert.equal(script('bin/check-upgrade.php').status,1);
  query("UPDATE donations SET status='available' WHERE id=1");
  assert.equal(script('bin/check-upgrade.php').status,0);
  schema('sql/migrations/001_handoffs.sql');
  assert.equal(query('SELECT food_name FROM donations WHERE id=1')[0].food_name,'Legacy loaf');
  const storage = mkdtempSync(join(tmpdir(),'foodshare-test-'));
  const base = await serve(t,storage);
  const second = await serve(t,storage);
  t.after(() => rmSync(storage,{ recursive:true, force:true }));
  const donor = browser(base), a = browser(base), b = browser(second), admin = browser(base);
  const listing = { food_name: 'Rice & vegetables', quantity: '2 trays', location: 'North',
    pickup_details:'Side entrance 7', handling_notes:'Contains soy', expiry_date:'2099-01-01' };
  const away = await donor.get('dashboard.php');
  assert.equal(away.headers.get('location'),'login.php');
  await donor.get('login.php');
  assert.equal((await donor.post('login.php',{email:'legacy@example.invalid',password})).status,303);
  await donor.get('donate.php');
  assert.equal((await donor.post('donate.php',{...listing,csrf:''})).status,403);
  assert.match((await donor.post('donate.php',{...listing,expiry_date:'2099-02-31'})).text,/Choose today/);
  assert.match((await donor.upload(listing,Buffer.from('not a photo'))).text,/Choose a JPG/);
  const png = readFileSync('public/img/food-platter.png');
  assert.equal((await donor.upload(listing,png)).status,303);
  const id = query('SELECT MAX(id) id FROM donations')[0].id;
  for (const [index,client] of [a,b,admin].entries()) {
    await client.get('register.php');
    const previous = client.cookie();
    assert.equal((await client.post('register.php',{name:'Partner '+index,email:'p'+index+'@example.invalid',
      role:'receiver', password,confirm_password:password})).status,303);
    assert.notEqual(client.cookie(),previous);
    await client.get('dashboard.php');
  }
  assert.equal((await a.get('admin.php')).status,403);
  query("UPDATE users SET role='admin' WHERE email='p2@example.invalid'");
  assert.equal((await admin.get('admin.php')).status,200);
  const browse = await a.get('browse.php?location=North');
  assert.match(browse.text,/Rice &amp; vegetables/);
  assert.doesNotMatch(browse.text,/Side entrance/);
  assert.equal((await a.get('photo.php?id='+id)).headers.get('content-type'),'image/png');
  const request = {donation_id:id,action:'request'};
  const both = await Promise.all([a.post('request.php',request), b.post('request.php',request)]);
  assert.ok(both.every(r=>r.status===303));
  const reservations = query('SELECT r.id,u.email FROM requests r JOIN users u ON u.id=r.receiver_id WHERE donation_id=?',[id]);
  assert.equal(reservations.length,1);
  const winner = reservations[0].email.startsWith('p0') ? a : b;
  const other = winner === a ? b : a;
  const action = {donation_id:id,request_id:reservations[0].id};
  await other.post('request.php',{...action,action:'approve'});
  await winner.post('request.php',{...action,action:'collect'});
  assert.equal(query('SELECT status FROM requests WHERE id=?',[action.request_id])[0].status,'pending');
  await donor.post('request.php',{...action,action:'approve'});
  assert.match((await winner.get('dashboard.php')).text,/Side entrance 7/);
  await other.post('request.php',{...action,action:'collect',received_kg:'7'});
  await winner.post('request.php',{...action,action:'collect',received_kg:'-1'});
  assert.equal(query('SELECT status FROM requests WHERE id=?',[action.request_id])[0].status,'approved');
  await winner.post('request.php',{...action,action:'collect',received_kg:'2.35'});
  await winner.post('request.php',{...action,action:'collect',received_kg:'99'});
  await admin.post('request.php',{...action,action:'cancel'});
  const result = query('SELECT status,received_kg,completed_at FROM requests WHERE id=?',[action.request_id])[0];
  assert.equal(result.status,'completed');
  assert.equal(result.received_kg,'2.35');
  assert.ok(result.completed_at);
  assert.match((await admin.get('admin.php')).text,/2\.35 kg reported received/);
  await donor.post('donate.php',{...listing,food_name:'Unweighed handoff'});
  const secondId = query('SELECT MAX(id) id FROM donations')[0].id;
  await a.post('request.php',{action:'request',donation_id:secondId});
  const secondRequest = query('SELECT id FROM requests WHERE donation_id=?',[secondId])[0].id;
  const secondAction = {donation_id:secondId,request_id:secondRequest};
  await donor.post('request.php',{...secondAction,action:'approve'});
  await a.post('request.php',{...secondAction,action:'collect'});
  const month = result.completed_at.slice(0,7);
  const report = spawnSync('php',['bin/impact-report.php',month],{encoding:'utf8'});
  assert.equal(report.status,0,report.stderr);
  const metrics = JSON.parse(report.stdout);
  assert.equal(Number(metrics.confirmed_pickups),2);
  assert.equal(Number(metrics.weighed_pickups),1);
  assert.equal(metrics.reported_kg,'2.35');
  await a.post('request.php',{action:'request',donation_id:1});
  const legacyRequest = query('SELECT id FROM requests WHERE donation_id=1')[0].id;
  await a.post('request.php',{action:'cancel',request_id:legacyRequest,donation_id:1});
  await b.post('request.php',{action:'request',donation_id:1});
  await a.post('request.php',{action:'cancel',request_id:legacyRequest,donation_id:1});
  assert.equal(query('SELECT status FROM donations WHERE id=1')[0].status,'requested');
  query("UPDATE donations SET expiry_date='2000-01-01' WHERE id=1");
  const expiredRequest = query("SELECT id FROM requests WHERE donation_id=1 AND status='pending'")[0].id;
  await donor.post('request.php',{action:'approve',donation_id:1,request_id:expiredRequest});
  assert.equal(query('SELECT status FROM requests WHERE id=?',[expiredRequest])[0].status,'pending');
  assert.equal(script('bin/expire.php').status,0);
  assert.equal(script('bin/expire.php').status,0);
  assert.equal(query('SELECT status FROM donations WHERE id=1')[0].status,'expired');
  assert.doesNotMatch((await a.get('browse.php')).text,/Legacy loaf/);
  const uid=query("SELECT id FROM users WHERE email='p0@example.invalid'")[0].id;
  await admin.post('admin.php',{action:'suspend',user_id:uid});
  assert.equal((await a.get('dashboard.php')).headers.get('location'),'login.php');
  assert.equal((await donor.get('logout.php')).status,405);
  await donor.get('dashboard.php');
  assert.equal((await donor.post('logout.php',{})).status,303);
  assert.equal((await donor.get('dashboard.php')).headers.get('location'),'login.php');
  await donor.get('login.php');
  for (let i=0; i<11; i++) {
    const res = await donor.post('login.php',{email:'throttle@example.invalid',password});
    assert.equal(res.status,i===10 ? 429 : 200);
  }
  console.log('Verified legacy upgrade, roles, photo handling, CSRF, concurrent reservations, receipt evidence, expiry, suspension and logout.');
});
