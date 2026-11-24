# Bootstrap MySQL Users and Grants

Creates the two schemas and three users this project needs, and applies the
grant matrix that enforces audit-log immutability.

Run every statement as a MySQL superuser (root), except where noted.

This procedure describes the production-like MySQL 8 setup and its `mdrrmo`
and `mdrrmo_test` schemas. The current local Windows installation is MariaDB
10.4 on port 3307 and instead uses `emergency_sys` and `emergency_sys_test`;
its service accounts are provisioned locally using the same least-privilege
matrix. Do not treat the local MariaDB setup as satisfying the MySQL 8
production requirement.

Configure `TRUSTED_PROXIES` as a comma-separated list of the exact proxy IPs or
CIDRs that connect to the app (for example, your load balancer or Cloudflare
egress ranges). Keep the default loopback entries for local development. Do
not use `*` unless the app is unreachable except through a controlled proxy;
otherwise clients can spoof forwarded IP headers. IP bans are stored for
moderation visibility and enforced with Laravel's RateLimiter for at most 24
hours; use phone bans for persistent blocking.

---

## 1. Confirm MySQL 8 and server defaults

    SELECT @@version;
    -- expect 8.0.x or higher

    SELECT @@sql_mode;
    -- expect STRICT_ALL_TABLES and ONLY_FULL_GROUP_BY to be present

    SELECT @@character_set_server, @@collation_server;
    -- expect utf8mb4 and utf8mb4_unicode_ci (or utf8mb4_0900_ai_ci)

If STRICT_ALL_TABLES is missing, set it in my.cnf:

    [mysqld]
    sql_mode=STRICT_ALL_TABLES,ONLY_FULL_GROUP_BY,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION

Then restart MySQL.

---

## 2. Create schemas

    CREATE DATABASE IF NOT EXISTS mdrrmo
      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

    CREATE DATABASE IF NOT EXISTS mdrrmo_test
      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

---

## 3. Create users

Replace the three passwords before running. Use different passwords per user,
and different passwords for development and production.

    CREATE USER IF NOT EXISTS 'mdrrmo_app'@'localhost'      IDENTIFIED BY 'REPLACE_APP_PASSWORD';
    CREATE USER IF NOT EXISTS 'mdrrmo_app'@'127.0.0.1'      IDENTIFIED BY 'REPLACE_APP_PASSWORD';

    CREATE USER IF NOT EXISTS 'mdrrmo_audit'@'localhost'    IDENTIFIED BY 'REPLACE_AUDIT_PASSWORD';
    CREATE USER IF NOT EXISTS 'mdrrmo_audit'@'127.0.0.1'    IDENTIFIED BY 'REPLACE_AUDIT_PASSWORD';

    CREATE USER IF NOT EXISTS 'mdrrmo_migrate'@'localhost'  IDENTIFIED BY 'REPLACE_MIGRATE_PASSWORD';
    CREATE USER IF NOT EXISTS 'mdrrmo_migrate'@'127.0.0.1'  IDENTIFIED BY 'REPLACE_MIGRATE_PASSWORD';

---

## 4. Create the grant probe table

    CREATE TABLE IF NOT EXISTS mdrrmo.grant_probe (
      id INT PRIMARY KEY,
      note VARCHAR(64) NOT NULL
    ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS mdrrmo_test.grant_probe LIKE mdrrmo.grant_probe;

---

## 5. Apply grants on mdrrmo

MySQL and MariaDB privileges are cumulative: a table-level `REVOKE` does not
cancel a privilege inherited from `mdrrmo.*`. Do not give the application
schema-wide DML rights. Grant runtime DML one table at a time, excluding
`audit_logs`; grant only `SELECT, INSERT` on `audit_logs` and the temporary
probe. Repeat these table grants after migrations add a table.

Clear any earlier schema-wide grants first. These statements do not remove
the user's global account or its grants on other schemas.

    REVOKE ALL PRIVILEGES ON mdrrmo.* FROM 'mdrrmo_app'@'localhost';
    REVOKE ALL PRIVILEGES ON mdrrmo.* FROM 'mdrrmo_app'@'127.0.0.1';
    REVOKE ALL PRIVILEGES ON mdrrmo.* FROM 'mdrrmo_audit'@'localhost';
    REVOKE ALL PRIVILEGES ON mdrrmo.* FROM 'mdrrmo_audit'@'127.0.0.1';

    -- Example for each runtime table other than audit_logs and grant_probe.
    GRANT SELECT, INSERT, UPDATE, DELETE ON mdrrmo.incident_reports
      TO 'mdrrmo_app'@'localhost';
    GRANT SELECT, INSERT, UPDATE, DELETE ON mdrrmo.incident_reports
      TO 'mdrrmo_app'@'127.0.0.1';

    -- Queue moderation (soft delete/restore and reject/reopen reports).
    GRANT SELECT, INSERT, UPDATE, DELETE ON mdrrmo.incident_reports
      TO 'mdrrmo_app'@'localhost';
    GRANT SELECT, INSERT, UPDATE, DELETE ON mdrrmo.incident_reports
      TO 'mdrrmo_app'@'127.0.0.1';

    -- Settings lists bans and lets admins add/remove them; progressive
    -- escalation updates ban metadata. Keep violation history append-only.
    GRANT SELECT, INSERT, UPDATE, DELETE ON mdrrmo.banned_ips
      TO 'mdrrmo_app'@'localhost';
    GRANT SELECT, INSERT, UPDATE, DELETE ON mdrrmo.banned_ips
      TO 'mdrrmo_app'@'127.0.0.1';
    GRANT SELECT, INSERT ON mdrrmo.rate_limit_violations
      TO 'mdrrmo_app'@'localhost';
    GRANT SELECT, INSERT ON mdrrmo.rate_limit_violations
      TO 'mdrrmo_app'@'127.0.0.1';
    GRANT SELECT, INSERT, DELETE ON mdrrmo.banned_phones
      TO 'mdrrmo_app'@'localhost';
    GRANT SELECT, INSERT, DELETE ON mdrrmo.banned_phones
      TO 'mdrrmo_app'@'127.0.0.1';

    -- The app can read and append to the audit log, but cannot change or
    -- remove existing rows. grant_probe uses this same protected matrix.
    GRANT SELECT, INSERT ON mdrrmo.audit_logs TO 'mdrrmo_app'@'localhost';
    GRANT SELECT, INSERT ON mdrrmo.audit_logs TO 'mdrrmo_app'@'127.0.0.1';
    GRANT SELECT, INSERT ON mdrrmo.grant_probe TO 'mdrrmo_app'@'localhost';
    GRANT SELECT, INSERT ON mdrrmo.grant_probe TO 'mdrrmo_app'@'127.0.0.1';

    -- The audit account is limited to the protected tables.
    GRANT SELECT, INSERT ON mdrrmo.audit_logs TO 'mdrrmo_audit'@'localhost';
    GRANT SELECT, INSERT ON mdrrmo.audit_logs TO 'mdrrmo_audit'@'127.0.0.1';
    GRANT SELECT, INSERT ON mdrrmo.grant_probe TO 'mdrrmo_audit'@'localhost';
    GRANT SELECT, INSERT ON mdrrmo.grant_probe TO 'mdrrmo_audit'@'127.0.0.1';

    -- Migration user: DDL + DML on the whole schema.
    GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES
      ON mdrrmo.* TO 'mdrrmo_migrate'@'localhost';
    GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES
      ON mdrrmo.* TO 'mdrrmo_migrate'@'127.0.0.1';

---

## 6. Apply grants on mdrrmo_test

Apply the same per-table matrix as Step 5: grant the app DML on each runtime
table except `audit_logs`, grant app and audit users only `SELECT, INSERT` on
`audit_logs` and `grant_probe`, and give the audit user no rights on other
tables. Do not add schema-wide DML grants.

For ban management, grant `SELECT, INSERT, UPDATE, DELETE` on
`mdrrmo_test.banned_ips`, `SELECT, INSERT, DELETE` on
`mdrrmo_test.banned_phones`, and `SELECT, INSERT` on
`mdrrmo_test.rate_limit_violations` to `mdrrmo_app` at `localhost` and
`127.0.0.1`, matching the production schema grants above. Keep violation logs
append-only; do not grant UPDATE or DELETE on `rate_limit_violations`.

    REVOKE ALL PRIVILEGES ON mdrrmo_test.* FROM 'mdrrmo_app'@'localhost';
    REVOKE ALL PRIVILEGES ON mdrrmo_test.* FROM 'mdrrmo_app'@'127.0.0.1';
    REVOKE ALL PRIVILEGES ON mdrrmo_test.* FROM 'mdrrmo_audit'@'localhost';
    REVOKE ALL PRIVILEGES ON mdrrmo_test.* FROM 'mdrrmo_audit'@'127.0.0.1';

    GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES
      ON mdrrmo_test.* TO 'mdrrmo_migrate'@'localhost';
    GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES
      ON mdrrmo_test.* TO 'mdrrmo_migrate'@'127.0.0.1';

---

## 7. Apply

    FLUSH PRIVILEGES;

---

## 8. Verify

As mdrrmo_app:

    mysql -u mdrrmo_app -p mdrrmo -e "SELECT 1"
    -- expect: 1

    mysql -u mdrrmo_app -p mdrrmo -e "INSERT INTO grant_probe (id, note) VALUES (1, 'a')"
    -- expect: success

    mysql -u mdrrmo_app -p mdrrmo -e "UPDATE grant_probe SET note='b' WHERE id=1"
    -- expect: ERROR 1142 (42000): UPDATE command denied

    mysql -u mdrrmo_app -p mdrrmo -e "DELETE FROM grant_probe WHERE id=1"
    -- expect: ERROR 1142 (42000): DELETE command denied

    mysql -u mdrrmo_app -p mdrrmo -e "ALTER TABLE grant_probe ADD COLUMN x INT"
    -- expect: ERROR 1142 (42000): ALTER command denied

As mdrrmo_audit:

    mysql -u mdrrmo_audit -p mdrrmo -e "SELECT 1"
    -- expect: 1

    mysql -u mdrrmo_audit -p mdrrmo -e "INSERT INTO grant_probe (id, note) VALUES (2, 'b')"
    -- expect: success

    mysql -u mdrrmo_audit -p mdrrmo -e "UPDATE grant_probe SET note='c' WHERE id=2"
    -- expect: ERROR 1142

As mdrrmo_migrate:

    mysql -u mdrrmo_migrate -p mdrrmo -e "ALTER TABLE grant_probe ADD COLUMN x INT"
    -- expect: success

---

## 9. What happens on Day 2

When `audit_logs` exists, first remove any schema-wide grants from the app and
audit accounts. Table-level grants are additive, so a schema-wide UPDATE or
DELETE would bypass a table-level restriction. Run these revokes first:

    REVOKE ALL PRIVILEGES ON mdrrmo.* FROM 'mdrrmo_app'@'localhost';
    REVOKE ALL PRIVILEGES ON mdrrmo.* FROM 'mdrrmo_app'@'127.0.0.1';
    REVOKE ALL PRIVILEGES ON mdrrmo_test.* FROM 'mdrrmo_app'@'localhost';
    REVOKE ALL PRIVILEGES ON mdrrmo_test.* FROM 'mdrrmo_app'@'127.0.0.1';
    REVOKE ALL PRIVILEGES ON mdrrmo.* FROM 'mdrrmo_audit'@'localhost';
    REVOKE ALL PRIVILEGES ON mdrrmo.* FROM 'mdrrmo_audit'@'127.0.0.1';
    REVOKE ALL PRIVILEGES ON mdrrmo_test.* FROM 'mdrrmo_audit'@'localhost';
    REVOKE ALL PRIVILEGES ON mdrrmo_test.* FROM 'mdrrmo_audit'@'127.0.0.1';

Then reapply the per-table app DML grants from Steps 5 and 6 for every runtime
table except `audit_logs`. Do not restore schema-wide grants. Apply the protected
table grants and flush:

    GRANT SELECT, INSERT ON mdrrmo.audit_logs TO 'mdrrmo_app'@'localhost';
    GRANT SELECT, INSERT ON mdrrmo.audit_logs TO 'mdrrmo_app'@'127.0.0.1';
    GRANT SELECT, INSERT ON mdrrmo_test.audit_logs TO 'mdrrmo_app'@'localhost';
    GRANT SELECT, INSERT ON mdrrmo_test.audit_logs TO 'mdrrmo_app'@'127.0.0.1';
    GRANT SELECT, INSERT ON mdrrmo.audit_logs TO 'mdrrmo_audit'@'localhost';
    GRANT SELECT, INSERT ON mdrrmo.audit_logs TO 'mdrrmo_audit'@'127.0.0.1';
    GRANT SELECT, INSERT ON mdrrmo_test.audit_logs TO 'mdrrmo_audit'@'localhost';
    GRANT SELECT, INSERT ON mdrrmo_test.audit_logs TO 'mdrrmo_audit'@'127.0.0.1';
    FLUSH PRIVILEGES;

Then drop the probe table and remove its grants (dropping a table may leave
stale table grants behind):

    DROP TABLE mdrrmo.grant_probe;
    DROP TABLE mdrrmo_test.grant_probe;
    REVOKE SELECT, INSERT ON mdrrmo.grant_probe FROM 'mdrrmo_app'@'localhost';
    REVOKE SELECT, INSERT ON mdrrmo.grant_probe FROM 'mdrrmo_app'@'127.0.0.1';
    REVOKE SELECT, INSERT ON mdrrmo_test.grant_probe FROM 'mdrrmo_app'@'localhost';
    REVOKE SELECT, INSERT ON mdrrmo_test.grant_probe FROM 'mdrrmo_app'@'127.0.0.1';
    REVOKE SELECT, INSERT ON mdrrmo.grant_probe FROM 'mdrrmo_audit'@'localhost';
    REVOKE SELECT, INSERT ON mdrrmo.grant_probe FROM 'mdrrmo_audit'@'127.0.0.1';
    REVOKE SELECT, INSERT ON mdrrmo_test.grant_probe FROM 'mdrrmo_audit'@'localhost';
    REVOKE SELECT, INSERT ON mdrrmo_test.grant_probe FROM 'mdrrmo_audit'@'127.0.0.1';
    FLUSH PRIVILEGES;

---

## 10. Why three users and not one

- `mdrrmo_app` runs the application. It cannot alter the schema and cannot
  modify or delete audit rows.
- `mdrrmo_audit` writes audit rows. It cannot read or write any other table.
- `mdrrmo_migrate` runs migrations. It is used only by `php artisan migrate
  --database=mysql_migrate`, never by runtime code.

If any user is ever compromised, the blast radius is bounded by its grants.
This is the load-bearing security property of the audit design, and the only
reason the "insert-only" guarantee in the PRD is real rather than aspirational.
