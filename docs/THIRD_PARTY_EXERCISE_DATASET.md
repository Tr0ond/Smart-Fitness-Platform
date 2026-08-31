# Third-party exercise dataset

Smart Fitness imports the development Exercise Library from
[hasaneyldrm/exercises-dataset](https://github.com/hasaneyldrm/exercises-dataset),
commit `7455efae41b330c265e7cd4b78dfa848e7ce5ebd` (the source revision used for
the current import).

## Dataset and instruction text

The non-media dataset, schema, tooling and multilingual instruction text are
released under the repository's MIT License. The importer keeps the English
instruction text in `bai_tap.huong_dan` and stores source identifiers and
attribution metadata in `bai_tap.thong_tin_bo_sung`.

## Images and animation GIFs

The `images/` thumbnails and `videos/` animation GIFs are **not** covered by
that MIT license. They are © Gym visual and are included in the source
repository with separate permission. Every imported record retains the source
attribution `© Gym visual — https://gymvisual.com/`. Their use is governed by
[Gym visual's Terms & Conditions](https://gymvisual.com/content/3-terms-and-conditions-of-use);
cloning this project does not grant a new media license. Imported binaries are
generated development artifacts and are intentionally ignored by Git.

The source repository's `LICENSE` and `NOTICE.md` are the authoritative terms;
review them before redistributing any media outside the development
environment.

## Reproducible checkout and import

The approved revision is fixed at:

```text
7455efae41b330c265e7cd4b78dfa848e7ce5ebd
```

From the repository root:

```powershell
New-Item -ItemType Directory -Force .tmp/exercises-dataset | Out-Null
git -C .tmp/exercises-dataset init
git -C .tmp/exercises-dataset remote add origin https://github.com/hasaneyldrm/exercises-dataset.git
git -C .tmp/exercises-dataset fetch --depth 1 origin 7455efae41b330c265e7cd4b78dfa848e7ce5ebd
git -C .tmp/exercises-dataset checkout --detach FETCH_HEAD
git -C .tmp/exercises-dataset rev-parse HEAD
```

The final command must print the exact approved revision. Then set the optional
metadata guard and seed:

```powershell
$env:EXERCISE_DATASET_PATH='../.tmp/exercises-dataset'
$env:EXERCISE_DATASET_COMMIT='7455efae41b330c265e7cd4b78dfa848e7ce5ebd'
Set-Location BE
php artisan db:seed --class=ExerciseDatasetSeeder --force
```

The importer is idempotent by stable external exercise code and stores source
ID/commit/attribution in `bai_tap.thong_tin_bo_sung`. The approved checkout
currently yields 1,324 exercises in the seeded development/test catalog.
Source binaries remain ignored; CI fetches this exact commit before seeding.
