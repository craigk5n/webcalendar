# WebCalendar Release Signing & File-Integrity Verification

This document is the operational runbook for the signed-manifest file-integrity
feature (GitHub issue [#233][issue]). Target audience:

- **Maintainers** who cut releases and need to manage the signing key.
- **Administrators** who want to verify a downloaded release independently,
  outside the app.

[issue]: https://github.com/craigk5n/webcalendar/issues/233

## Table of Contents

- [Threat Model](#threat-model)
- [Architecture](#architecture)
- [Generating the Signing Keypair](#generating-the-signing-keypair)
- [Installing the Public Key](#installing-the-public-key)
- [Storing the Private Key in GitHub](#storing-the-private-key-in-github)
- [Key Rotation](#key-rotation)
- [Compromise Response](#compromise-response)
- [Manual Verification of a Release](#manual-verification-of-a-release)
- [Sigstore Cosign Verification](#sigstore-cosign-verification)
- [Troubleshooting](#troubleshooting)

---

## Threat Model

**What this feature catches:**

- Opportunistic webshell drops (attacker adds `shell.php`, `cmd.php`, etc. into
  the install directory).
- Silent modification of shipped files (attacker edits an existing `.php` to
  inject code).
- Partial upgrade damage (missing files after a failed `unzip`).

**What this feature does NOT catch:**

- A targeted attacker who rewrites `security_audit.php` itself — on-disk
  self-audit has an inherent ceiling. If an attacker can plant files they can
  also modify the code that checks for planted files.
- Anything in the database (WebCalendar stores blobs and uploads in the DB; no
  filesystem upload directory to scan).
- Compromise of the GitHub Actions signing secret — covered by
  [Compromise Response](#compromise-response) below.

**Why we ship it anyway:** most real-world WebCalendar compromises are
opportunistic — scanners drop a file with a known PHP filename and move on.
A signed manifest catches those in one click from the admin audit page.

---

## Architecture

Three files work together to provide file-integrity verification. All three
live at the **repository root** and ship inside the release zip:

| File | Purpose | Source |
|------|---------|--------|
| `release-signing-pubkey.pem` | Ed25519 **public** key, PEM-wrapped. Committed to the repo. | Committed once; rotated rarely. |
| `MANIFEST.sha256` | Sorted `<sha256>  <relpath>` text file listing every shipped file. Header contains version, build timestamp, git SHA. | Generated fresh on every release by `.github/workflows/release.yml`. |
| `MANIFEST.sha256.sig` | Detached Ed25519 signature of `MANIFEST.sha256`, base64-encoded. | Generated fresh on every release, signed with the private key from the `RELEASE_SIGNING_KEY` GitHub Actions secret. |

The admin audit page (`security_audit.php`) verifies the signature, parses the
manifest, walks the install directory, and reports MISSING / MODIFIED / EXTRA
files with severity badges. See `SECURITY_AUDIT_STATUS.md` at the repo root for
the full story-by-story implementation log.

---

## Generating the Signing Keypair

A keypair is generated once at project setup and (per policy) every time the
private key is rotated or suspected compromised.

### Prerequisites

- PHP 8.1+ with the `sodium` extension (bundled with PHP 7.2+; verify via
  `php -m | grep sodium`).
- A secure terminal — the private key will be printed to stdout. Don't run this
  over screen-share or in a terminal with session recording.

### Procedure

```bash
# From the repository root:
php tools/generate-release-key.php > /tmp/webcal-release-keys.txt
chmod 600 /tmp/webcal-release-keys.txt
```

The temp file will contain two copy-paste-ready blocks:

1. **The GitHub Actions secret value** — an 88-character base64 string. This is
   the *private* half. Never commit it anywhere.
2. **The public-key PEM block** — a three-line block wrapped in
   `-----BEGIN WEBCALENDAR RELEASE PUBLIC KEY-----` / `-----END WEBCALENDAR
   RELEASE PUBLIC KEY-----` markers. This is the *public* half. Safe to commit.

Running the script twice produces different keypairs — the randomness comes
from libsodium's CSPRNG. This is verified by the test
`testTwoGenerationsProduceDifferentKeys`.

If libsodium is missing, the script exits non-zero with a clear error message
before generating anything.

---

## Installing the Public Key

Commit the public-key PEM block as `release-signing-pubkey.pem` at the repo
root:

```bash
# Extract just the PEM block from the temp file:
awk '/BEGIN WEBCALENDAR RELEASE PUBLIC KEY/,/END WEBCALENDAR RELEASE PUBLIC KEY/' \
  /tmp/webcal-release-keys.txt > release-signing-pubkey.pem

git add release-signing-pubkey.pem
git commit -m "chore: install new release signing public key"
git push
```

`release-signing-pubkey.pem` is already listed in `release-files` so it ships
in every release zip.

The `.gitignore` has a defensive rule (`release-signing-privkey*`) that rejects
any filename starting with `release-signing-privkey`. Extra paranoia against
accidentally committing a private-key dump.

---

## Storing the Private Key in GitHub

The private key lives exclusively in the `RELEASE_SIGNING_KEY` GitHub Actions
environment secret, scoped to the `release` environment. This ensures:

- Only workflows that explicitly declare `environment: release` receive the
  secret (our release workflow does; fork-PR workflows do not).
- No human — including the maintainer — needs to hold a copy locally after
  setup.

### Procedure

1. Open the repo's Environments settings page:
   `https://github.com/<owner>/<repo>/settings/environments/release`
   (create the `release` environment first if it doesn't exist;
   `gh api -X PUT repos/<owner>/<repo>/environments/release` creates it with
   no protection rules).
2. Click **Add environment secret**.
3. Name: `RELEASE_SIGNING_KEY`.
4. Value: paste the 88-character base64 line from the temp file (the
   `WhkIfA09...`-style string that appears after the "paste this value"
   label).
5. Save.
6. Scrub the local copy:

   ```bash
   shred -u /tmp/webcal-release-keys.txt
   ```

### Verify the Paste

A manual-trigger workflow (`.github/workflows/verify-release-signing.yml`)
exists precisely for this:

1. Go to the repo's **Actions** tab.
2. Find **Verify Release Signing Key** in the left sidebar.
3. Click **Run workflow** → **Run workflow**.
4. A green run confirms the pasted secret's public half matches the committed
   `release-signing-pubkey.pem`.

The verifier **never logs the secret**. Even on mismatch it prints only a
generic `FAIL:` reason (covered by the
`testVerifySecretKeyEnvReasonDoesNotLeakSecret` unit test).

---

## Key Rotation

Rotate the signing keypair:

- **On a routine cadence** — once every 1-2 years, or whenever the project
  changes hands or machines.
- **Immediately** on suspected compromise (see next section).
- **On major version boundaries** if you want signature chains aligned with
  release families.

### Procedure

1. Generate a fresh keypair (see [Generating the Signing
   Keypair](#generating-the-signing-keypair)).
2. Overwrite `release-signing-pubkey.pem` with the new PEM. Commit and push.
3. Update the `RELEASE_SIGNING_KEY` GitHub secret with the new base64 value
   (Settings → Environments → `release` → **Update**).
4. Run the **Verify Release Signing Key** workflow to confirm the new pair
   matches.
5. Cut a new release so the new signature propagates. Older releases remain
   signed with the previous key and will fail verification on fresh installs
   after rotation.

### Transition Period (Optional)

If you want to avoid breaking verification for users who haven't upgraded yet:

- Keep the OLD public key in the repo temporarily at a second path
  (e.g. `release-signing-pubkey-previous.pem`).
- Patch the audit code to accept either pubkey (loop over both in
  `render_file_integrity_section()`).
- Drop the old pubkey after a release or two.

This path is not yet implemented in code — it's a documented extension if the
need arises.

---

## Compromise Response

If the private key is suspected compromised (GitHub account takeover,
accidental leak, laptop loss during rotation, etc.):

1. **Rotate immediately** — follow the key rotation procedure above.
2. **Delete the suspect secret** from the GitHub UI before pasting the new
   value. This invalidates any in-flight signing runs using the old secret.
3. **Publish a security advisory** via
   [GitHub Security Advisories][gh-advisory] on the repo. State:
   - The rotation date.
   - Which releases were signed with the now-untrusted key (list zip filenames
     and SHAs).
   - Instructions for users: re-download the most recent release (signed with
     the new key) or continue using their previous install with signature
     verification disabled.
4. **Notify in CHANGELOG.md** with a `### Security` entry.
5. **Audit the repo's access list**: Settings → Collaborators and teams; rotate
   any developer tokens that had access to the signing secret.

[gh-advisory]: https://github.com/craigk5n/webcalendar/security/advisories

---

## Manual Verification of a Release

Admins can verify a downloaded release zip independently of the audit page —
useful when you don't trust the running install, or want to check before
unzipping.

### Pure-PHP (Recommended)

No extra tooling required. PHP 7.2+ ships with libsodium.

```bash
# Assumes you've extracted the release zip and cd'd into it.
php -r '
  $manifest = file_get_contents("MANIFEST.sha256");
  $sig = base64_decode(trim(file_get_contents("MANIFEST.sha256.sig")), true);
  $pem = file_get_contents("release-signing-pubkey.pem");
  preg_match("/-----BEGIN.*?-----(.+?)-----END/s", $pem, $m);
  $pub = base64_decode(preg_replace("/\s+/", "", $m[1]), true);
  $ok = sodium_crypto_sign_verify_detached($sig, $manifest, $pub);
  echo $ok ? "SIGNATURE VALID\n" : "SIGNATURE INVALID\n";
  exit($ok ? 0 : 1);
'
```

Exit code `0` = signature valid. Non-zero = reject the release and report the
failure.

Once the signature verifies, you can cross-check the individual file hashes
with GNU `sha256sum` — the manifest uses the exact same format:

```bash
# From inside the extracted release tree:
grep -v '^#' MANIFEST.sha256 | sha256sum -c --strict --quiet
```

Any output from that command indicates a file whose on-disk hash doesn't match
the manifest — a tamper signal.

### Node.js Alternative

If you prefer to verify from outside the PHP ecosystem entirely:

```javascript
// verify.mjs — run with `node verify.mjs`
import { readFileSync } from 'node:fs';
import sodium from 'libsodium-wrappers';
await sodium.ready;

const manifest = readFileSync('MANIFEST.sha256');
const sig = Buffer.from(readFileSync('MANIFEST.sha256.sig', 'utf8').trim(), 'base64');
const pem = readFileSync('release-signing-pubkey.pem', 'utf8');
const b64 = pem.match(/-----BEGIN[^-]+-----([\s\S]+?)-----END/)[1].replace(/\s/g, '');
const pub = Buffer.from(b64, 'base64');

const ok = sodium.crypto_sign_verify_detached(sig, manifest, pub);
console.log(ok ? 'SIGNATURE VALID' : 'SIGNATURE INVALID');
process.exit(ok ? 0 : 1);
```

Requires `npm install libsodium-wrappers`.

### Why Not `openssl dgst -verify`?

OpenSSL supports Ed25519 starting in 1.1.1, but it expects a different public-
key encoding (PKCS#8 / SubjectPublicKeyInfo) than the raw 32-byte body we ship.
Using OpenSSL requires wrapping our raw key in the SPKI prefix:

```bash
# One-time conversion from our PEM to an openssl-compatible PEM:
{ printf '\x30\x2a\x30\x05\x06\x03\x2b\x65\x70\x03\x21\x00';
  grep -v '^-----' release-signing-pubkey.pem | tr -d '\n' | base64 -d;
} | base64 -w 64 | {
  echo '-----BEGIN PUBLIC KEY-----';
  cat;
  echo '-----END PUBLIC KEY-----';
} > /tmp/webcal-pub-openssl.pem

# Decode the detached sig from base64 to raw 64 bytes:
base64 -d MANIFEST.sha256.sig > /tmp/webcal.sig

# Verify:
openssl pkeyutl -verify \
  -pubin -inkey /tmp/webcal-pub-openssl.pem \
  -rawin -in MANIFEST.sha256 \
  -sigfile /tmp/webcal.sig
```

The pure-PHP path is simpler and recommended.

---

## Sigstore Cosign Verification

Every tagged release is **also** signed keyless with [Sigstore cosign][cosign]
via GitHub Actions' OIDC identity. This gives you an independent verification
path that does not rely on the WebCalendar-maintained Ed25519 key at all:

- The private signing key is **ephemeral** — generated in-memory during the
  release workflow, never stored anywhere.
- The signing identity is a short-lived X.509 certificate issued by
  [Fulcio][fulcio] tying the signature to the GitHub Actions OIDC subject
  `https://github.com/craigk5n/webcalendar/.github/workflows/release.yml@refs/heads/release`.
- The signing event is recorded in [Rekor][rekor], Sigstore's public
  transparency log — tamper-evident, publicly auditable.

[cosign]: https://docs.sigstore.dev/cosign/overview/
[fulcio]: https://docs.sigstore.dev/fulcio/overview/
[rekor]: https://docs.sigstore.dev/rekor/overview/

### Artifacts

Each tagged release publishes two extra assets alongside the zip:

- `WebCalendar-VERSION.zip.sig` — detached Ed25519-over-SHA-256 signature.
- `WebCalendar-VERSION.zip.pem` — the Fulcio-issued certificate (contains the
  public key + the OIDC subject that signed).

Both are uploaded by the `Upload cosign signature` / `Upload cosign certificate`
steps in `.github/workflows/release.yml`.

### Verifying a Release With Cosign

Install cosign: https://docs.sigstore.dev/cosign/installation/

From a directory holding all three files (zip, .sig, .pem) for the same
release:

```bash
VERSION=1.9.17  # set to the release you're verifying
cosign verify-blob \
  --certificate WebCalendar-${VERSION}.zip.pem \
  --signature   WebCalendar-${VERSION}.zip.sig \
  --certificate-identity-regexp \
      '^https://github\.com/craigk5n/webcalendar/\.github/workflows/release\.yml@refs/heads/release$' \
  --certificate-oidc-issuer https://token.actions.githubusercontent.com \
  WebCalendar-${VERSION}.zip
```

Expected output: `Verified OK`. Any tampering with the zip, the signature,
the certificate, or the identity-pin regex → verification fails.

### What the identity-pin regex does

The `--certificate-identity-regexp` flag tells cosign which OIDC subject is
considered authoritative for this artifact. The regex above pins three things:

1. **Repository**: `craigk5n/webcalendar` — a zip signed by any other
   repository's workflow will fail.
2. **Workflow path**: `.github/workflows/release.yml` — a zip signed by some
   *other* workflow in the same repo (e.g. a test workflow) will fail.
3. **Branch**: `refs/heads/release` — a zip signed by a run on a feature
   branch or fork will fail.

If an attacker creates a release tag in a fork, cosign will reject it
because the OIDC subject encodes the *source* repo, not the target.

### Complementary to, Not a Replacement for, the Manifest Signature

The two signing schemes check different things:

| Scheme | Answers the question |
|--------|---------------------|
| Ed25519 manifest signature | "Are the files on disk byte-identical to what the maintainer intended?" |
| Cosign zip signature | "Was this zip produced by a trusted GitHub Actions workflow run from the official repo?" |

Both are defense-in-depth. The manifest signature protects every shipped file
individually. The cosign signature protects the zip as a whole and proves
provenance via Sigstore's public infrastructure.

---

## Troubleshooting

### "Manifest files not present" on a fresh install

One or more of `release-signing-pubkey.pem`, `MANIFEST.sha256`, or
`MANIFEST.sha256.sig` is missing. Expected for source checkouts and pre-v1.9.x
release zips. For release zips >= v1.9.x, re-download the zip — the files
should all be at the root.

### "Manifest signature FAILED: ..." on a legit install

Most likely: the release you downloaded was signed with an older / different
key than the `release-signing-pubkey.pem` shipped in that zip. This shouldn't
happen for a release cut by the official workflow (the workflow pairs them).

If it does, check:

1. Did you mix files from two different release zips?
2. Is `release-signing-pubkey.pem` the one from the SAME zip as `MANIFEST.sha256`?
3. Are all three files LF-line-ended (not CRLF)? `file MANIFEST.sha256` should
   say `ASCII text` without `CRLF line terminators`. Some Windows extract tools
   mangle text files; unzip with `-a` at your own risk, or use `git` /
   `bsdtar`.

### Signing step fails in the release workflow

Likely causes, in order of probability:

- `RELEASE_SIGNING_KEY` is not set in the `release` environment. Paste the
  base64 value and re-run the workflow.
- The pasted secret doesn't match the committed pubkey. Run **Verify Release
  Signing Key** to diagnose.
- `release-files` lists a file that doesn't exist. `build-manifest.php` is
  strict about this; it exits non-zero with the offending path in the error
  message. Add the missing file or remove the stale line.
