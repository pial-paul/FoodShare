# Contributing

Start with a concrete donor, receiver or coordinator problem. For changes to pickup rules, describe the actor, current state, intended transition and effect on receipt reporting. Use synthetic examples and anonymize screenshots.

Branch from current main. Keep PHP routes in public, shared behavior in src and database changes in numbered migration files. Preserve existing data or explain a recovery plan. Use bound parameters, escape at rendering, check authorization at mutation time and provide a visible label for every input.

Follow the local verification guide. A pull request should identify the user problem, changed behavior, test commands and results, any accessibility effects, the upgrade path and remaining limitations. Link a screenshot for a visible change. Keep credentials, uploaded food images, partner records and environment files out of commits. [Verification guide](docs/verification.md)

Use short imperative commit subjects. Reviewers should exercise the normal entry point and challenge any impact claim whose numerator, denominator or data source is missing. Disclose security concerns through the private process. [Security](SECURITY.md)
