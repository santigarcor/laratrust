const activeVersion = '5.1';

module.exports = {
  title: 'Laratrust',
  base: '/laratrust/',
  description: 'Laravel 5 role-based access control package',
  head: [
    ['link', { rel: 'icon', href: '/favicon.ico' }]
  ],
  themeConfig: {
    logo: '/logo.svg',
    activeVersion: getActiveVersion(),
    lastUpdated: 'Last Updated',
    // Assumes GitHub. Can also be a full GitLab url.
    repo: 'santigarcor/laratrust',
    // Customising the header label
    // Defaults to "GitHub"/"GitLab"/"Bitbucket" depending on `themeConfig.repo`
    repoLabel: 'Contribute!',

    // Optional options for generating "Edit this page" link

    // if your docs are in a different repo from your main project:
    docsRepo: 'santigarcor/laratrust',
    // if your docs are not at the root of the repo:
    docsDir: 'docs',
    // if your docs are in a specific branch (defaults to 'master'):
    docsBranch: 'master',
    // defaults to false, set to true to enable
    editLinks: true,
    // custom text for edit link. Defaults to "Edit this page"
    editLinkText: 'Help us improve this page!',
    sidebar: {
      '/docs/5.0/': getDocsNavBar(),
      '/docs/5.1/': getDocsNavBar(),
    },
    nav: [
      { text: 'Docs', link: getActiveVersion().link },
      { text: 'Version', items: getVersionsLinks() },
      { text: 'GitHub', link: 'https://github.com/santigarcor/laratrust' },
    ]
  }
}

function getVersionsLinks() {
  return [
    { text: '5.1', link: '/docs/5.1/' },
    { text: '5.0', link: '/docs/5.0/' },
    { text: '<5.0', link: 'https://laratrust.readthedocs.io/' },
  ].sort((a, b) => a.text > b.text);
}

function getActiveVersion() {
  return getVersionsLinks().find(item => item.text == activeVersion);
}

function getDocsNavBar() {
  return [
    '',
    'upgrade',
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
    'troubleshooting',
    'license',
    'contributing',
  ];
}