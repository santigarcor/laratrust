# Contributing

Please report any issue you find in the package or in the docs.

Pull requests are welcome. Before you open one, run:

```bash
composer test        # The test suite
composer analyse     # PHPStan
composer format      # Pint
composer refactor    # Rector
```

To try the admin panel without an application, run `composer serve`. It starts a demo application with some roles, permissions and users at [http://localhost:8000/laratrust](http://localhost:8000/laratrust).

If you change the panel styles or scripts, rebuild them with `npm ci && npm run panel:build`. The Node version is in the `.nvmrc` file.
