BookQu audit patch — application instructions

1. Download apply_bookqu_agathan_audit.py and copy it to the root of your local BookQu repository.
2. Checkout the branch: git checkout agathan
3. Run: python apply_bookqu_agathan_audit.py
4. Inspect: git diff --check && git diff --stat && git diff
5. Run focused tests and the full suite:
     php artisan test --filter=SingaPayIntegrationTest
     php artisan test --filter=PaymentActionTest
     php artisan test --filter=SettingsTest
     php artisan test
     npm run build
6. Review the complete diff. Do not weaken assertions merely to get a green test result.
7. Commit and push the reviewed changes to branch agathan. GitHub Actions should run because the patch adds agathan to tests.yml.

The script refuses to run unless the current branch is exactly `agathan`, and it checks the expected code blocks in every file before writing anything. It does not commit or push. If the code has changed since this audit, the script stops so the patch can be adapted against the new implementation.

Important: the connected GitHub integration denied direct remote writes with HTTP 403, so this session has not changed the remote GitHub branch. The script is the local patch route.
