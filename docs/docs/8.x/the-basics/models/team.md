# Team

::: tip IMPORTANT
Only applies if you are using the teams feature.
:::

```php
<?php

namespace App;

use Laratrust\Models\Team as TeamModel;

class Team extends TeamModel
{
}
```

The `Team` model has three main attributes:

- `name` — Unique name for the Team, used for looking up team information in the application layer. For example: "my-team", "my-company".
- `display_name` — Human readable name for the Team. Not necessarily unique and optional. For example: "My Team", "My Company".
- `description` — A more detailed explanation of what the Team does. Also, optional.

Both `display_name` and `description` are optional; their fields are nullable in the database.
