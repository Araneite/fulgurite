# Contributing

Thank you for considering a contribution to Fulgurite.

This project accepts public collaboration while keeping a clear product direction and a contributor-first licensing policy.

## Before You Start

Please read:

- [README.md](README.md)
- [ROADMAP.md](ROADMAP.md)
- [GOVERNANCE.md](GOVERNANCE.md)
- [CONTRIBUTOR_TERMS.md](CONTRIBUTOR_TERMS.md)
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)
- [SECURITY.md](SECURITY.md)

For functional context, start with [Concepts and Navigation](docs/en/03-concepts-and-navigation.md).

## What To Contribute

Good contribution candidates include:

- reproducible bug fixes
- focused documentation improvements
- tests for existing behavior
- accessibility and usability improvements
- security hardening discussed with maintainers
- small feature work aligned with [ROADMAP.md](ROADMAP.md)

Large architectural changes, licensing-sensitive work, paid-feature boundaries, plugin architecture and deployment packaging should be discussed before implementation.

## Pull Request Workflow

1. Open an issue first for large or ambiguous work.
2. Create a focused branch.
3. Keep the change as small as practical.
4. Update documentation when behavior, configuration, API or setup flow changes.
5. Add or update tests when the change touches shared behavior or security-sensitive code.
6. Include manual verification notes in the pull request.
7. Confirm that you accept [CONTRIBUTOR_TERMS.md](CONTRIBUTOR_TERMS.md).

Suggested PR confirmation:

```text
I have read and agree to CONTRIBUTOR_TERMS.md for this contribution.
```

## Coding Expectations

- Follow the existing structure and style.
- Keep source code comments in English.
- Avoid unrelated refactors.
- Do not commit secrets, runtime databases, local `.env` files or production dumps.
- Keep user-facing copy consistent with the documentation language being edited.
- Prefer clear, testable changes over broad rewrites.

## Documentation Expectations

Root repository documents are written in English.

French convenience versions live in [project/fr](project/fr/README.md). User documentation lives in [docs/en](docs/en/01-index.md) and [docs/fr](docs/fr/01-index.md).

When a contribution changes user-visible behavior, update the relevant documentation page and add links where helpful.

## Contributor Ownership

You keep ownership of the original material you submit. You grant the project the permissions needed to review, merge, maintain and distribute the contribution under the public source-available repository terms.

Contributor-authored features will not be moved into a paid-only, commercial-only, hosted premium or paid API feature without prior written agreement from the contributor.

See [CONTRIBUTOR_TERMS.md](CONTRIBUTOR_TERMS.md) for the full terms.
