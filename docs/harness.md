# The Docker harness

`docker/` runs this package with every `omnilex/*` source installed - from GitHub (branch 1.x),
or from the checkouts beside this one when `OMNILEX_PLUGINS=../..` is set in `docker/.env` - and
a console that asks the real services.

```sh
cd docker && cp .env.dist .env       # PISTE_CLIENT_ID and PISTE_CLIENT_SECRET, when you have them
docker compose run --rm omnilex sources
```

| Command | |
|---|---|
| `sources` | the sources installed and configured, what each does, holds, filters by and reads |
| `search <source> [text]` | `--title`, `--number`, `--from`, `--to`, `--at`, `--sort` |
| `recent <source> <since>` | what is new since a day |
| `text <source> <id>` | a text as in force `--at` a day (JSON; `--full` for the whole content) |
| `article <source> <id> [number]` | one article `--at` a day (JSON) |
| `decision <source> <id>` | one decision (JSON; `--full`) |
| `citations <source> <id>` | the links, both ways; `--limit` each way |
| `test` | every package's tests |

`search` and `recent` take `--kind` (`text`, `article`, `decision`), `--jurisdiction`,
`--subject`, `--type` (repeatable, the source's own codes), `--limit`, `--cursor`, and `--format`
(`table`, `json`).

The configured sources are `eurlex` and `administratif` (no key), `legifrance` and `judilibre`
(when `PISTE_CLIENT_ID` and `PISTE_CLIENT_SECRET` are set; `PISTE_SANDBOX=1` for the sandbox
application's credentials).

```sh
docker compose run --rm omnilex text eurlex 32016R0679 --at 2018-06-01
docker compose run --rm omnilex article eurlex 32016R0679 17
docker compose run --rm omnilex decision eurlex ECLI:EU:C:2014:317
docker compose run --rm omnilex citations eurlex 32016R0679 --limit 10
docker compose run --rm omnilex search eurlex "protection des données" --kind text --type REG --from 2016-01-01
docker compose run --rm omnilex search administratif urbanisme --jurisdiction CE --from 2026-09-01
docker compose run --rm omnilex recent administratif 2026-10-01 --jurisdiction CE
docker compose run --rm omnilex article legifrance LEGITEXT000006070987 L36-11 --at 2018-01-01
docker compose run --rm omnilex search judilibre expropriation --jurisdiction cc --from 2020-01-01
```

The image is `php:8.4-cli-alpine` with Composer; the harness's packages live in the `harness`
volume of the `omnilex-harness` project. The sources are cloned from GitHub as plain git
repositories over HTTPS: no GitHub API (anonymous calls are rate limited), no ssh in the image.
