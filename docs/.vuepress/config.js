const activeVersion = '5.1';

module.exports = {
  title: 'Laratrust',
  description: 'Laravel 5 role-based access control package',
  head: [
    ['link', { rel: 'icon', href: '/laratrust.svg' }]
  ],
  themeConfig: {
    activeVersion: getActiveVersion(),
    lastUpdated: 'Last Updated',
    sidebar: {
      '/docs/5.1/': [
        '',
        'installation',
        {
          title: 'Configuration',
          children: [
            'configuration/after-installation',
            'configuration/migrations',
            'configuration/teams',
            ['configuration/models/role', 'Model - Role'],
            ['configuration/models/permission', 'Model - Permission'],
            ['configuration/models/team', 'Model - Team'],
            ['configuration/models/user', 'Model - User'],
            'configuration/seeder',
          ]
        },
        {
          title: 'Usage',
          children: [
            'usage/concepts',
            'usage/events',
            'usage/middleware',
            'usage/soft-deleting',
            'usage/blade-templates',
          ]
        },
      ],
    },
    nav: [
      { text: 'Docs', link: getActiveVersion().link },
      { text: 'GitHub', link: 'https://github.com/santigarcor/laratrust' },
      { text: 'Versions', items: getVersionsLinks() }
    ]
  }
}

function getVersionsLinks() {
  return [
    { text: '5.1', link: '/docs/5.1/' },
    { text: '<5.1', link: 'https://laratrust.readthedocs.io/' },
  ].sort((a, b) => a.text > b.text);
}

function getActiveVersion() {
  return getVersionsLinks().find(item => item.text == activeVersion);
}