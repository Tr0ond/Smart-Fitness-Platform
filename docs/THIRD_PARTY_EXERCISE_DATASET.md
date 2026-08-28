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
