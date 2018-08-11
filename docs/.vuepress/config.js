const activeVersion = '5.1';

module.exports = {
  title: 'Laratrust',
  description: 'Laravel 5 role-based access control package',
  head: [
    ['link', { rel: 'icon', href: '/laratrust.svg' }]
  ],
  themeConfig: {
    activeVersion: getActiveVersion(),
    displayAllHeaders: true,
    sidebar: {

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
    { text: '5.0', link: '/docs/5.0/' },
    { text: '5.1', link: '/docs/5.1/' },
  ].sort(item => item.text);
}

function getActiveVersion() {
  return getVersionsLinks().find(item => item.text == activeVersion);
}