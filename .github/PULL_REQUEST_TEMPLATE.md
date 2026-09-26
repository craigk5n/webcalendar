## Summary

Brief description of the changes.

## Related Issues

Fixes #

## Checklist

- [ ] `make check` passes — compile, PHPStan and PHPUnit, the same steps CI runs
- [ ] `CHANGELOG.md` updated under `## [Unreleased]`
- [ ] No breaking changes, or they are described above
- [ ] Documentation updated (if applicable)

<!--
`make check` runs what CI runs, in the same order, so a green result here
predicts a green pipeline. If you cannot run it, say so in the summary rather
than ticking the box.

Fixing an environment-specific bug? Including the reporter's
`php bin/webcal.php diagnose` output, or your own from a machine that
reproduces it, saves a round trip. It contains no passwords, tokens, host
names or email addresses; see docs/troubleshooting.md.
-->
