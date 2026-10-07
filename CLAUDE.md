# Theme release workflow

This theme is its own git repo and is versioned with [semver](https://semver.org). Every change to the theme
goes through a branch and ends in a tagged release. Never commit theme changes straight to `main`.

The version lives in **three places that must always match**: `style.css` (`Version:`), `readme.txt`
(`Stable tag:`) and `package.json` (`"version"`, plus the two root
`"version"` entries at the top of `package-lock.json`). `npm run package` reads `style.css` to name the zip
(`<theme>-v<version>.zip`).

## Steps

1. **Branch** off an up-to-date `main`: `git switch -c <type>/<short-name>` (`feat/…`, `fix/…`, `chore/…`).
   Commit the work there in normal, focused commits.
2. **Pick the next version** from the latest tag (`git describe --tags --abbrev=0`, or `style.css` if there are
   no tags yet):
   - **patch** (`1.0.0 → 1.0.1`): bug fixes, style tweaks, copy, no new features.
   - **minor** (`1.0.1 → 1.1.0`): new blocks, fields, CPTs, CLI commands, options. Backwards compatible.
   - **major** (`1.1.0 → 2.0.0`): anything that breaks existing content or sites, e.g. renamed or removed block
     fields that need a migration (`wp <theme> fields migrate`), removed blocks/templates, raised PHP/WP/plugin
     requirements.
   If the bump level isn't obvious, ask before choosing it.
3. **Record the release** on the branch, as the last commit (`Release X.Y.Z`):
   - Add a changelog entry at the **top** of `== Changelog ==` in `readme.txt` (newest first), in the existing
     format. Write it for the person updating a site: what changed and anything they must run afterwards.
     ```
     = 1.1.0 - Oct 7 2026 =
     * Added: Steps block.
     * Fixed: CardGrid icons misaligned on mobile.
     * Upgrade: run `wp <theme> fields migrate` after deploying.
     ```
   - Bump `Version:` in `style.css`, `Stable tag:` in `readme.txt` and `"version"` in `package.json` (and the root of `package-lock.json`).
     Check that all three match: `grep -m1 'Version:' style.css; grep 'Stable tag:' readme.txt; grep -m1 '"version"' package.json`.
   - Run `npm run build` and confirm it compiles before merging.
4. **Merge** into `main`: `git switch main && git merge --no-ff <branch>`, then delete the branch.
5. **Tag** the merge commit with the exact `style.css` version, no `v` prefix:
   `git tag -a X.Y.Z -m "X.Y.Z"`.
6. **Package** (optional, when a zip is needed for deployment): `npm run package` → `../<theme>-vX.Y.Z.zip`.
7. **Push** `main`, the tag (`git push origin main X.Y.Z`) and, if wanted, the branch. Pushing is outward-facing:
   ask before pushing, and skip it if the repo has no remote.

Several branches can go into one release: merge them without bumping and do step 3 on a short `release/X.Y.Z`
branch that collects the changelog for all of them. Docs-only changes (this file, comments) don't need their
own release; they ride along with the next one.

Forks of roadmap-starter (e.g. `the-newly`) have their own independent version line and tags. Porting a change
from roadmap-starter into a fork is a normal change in the fork and gets a fork release.
