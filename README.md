schema-dumper
=============

Generate an XML file containing the database schema of the provided database.

Example usage:

1. Generate XML
```
 ./bin/console db:dump mysql://username:password@localhost/dbname

```

2. Generate XML for perticular table.
```
./bin/console db:dump mysql://username:password@localhost/dbname  --tableName=user

```

