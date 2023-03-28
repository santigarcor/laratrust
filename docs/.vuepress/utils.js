const ACTIVE_VERSION = "8.x";

function getDocsNavBar(version) {
  switch (version) {
    case "5.0":
    case "5.1":
    case "5.2":
      return [
        "upgrade",
        "",
        "installation",
        {
          title: "Configuration",
          children: [
            "configuration/after-installation",
            "configuration/migrations",
            "configuration/teams",
            ["configuration/models/role", "Model - Role"],
            ["configuration/models/permission", "Model - Permission"],
            ["configuration/models/team", "Model - Team"],
            ["configuration/models/user", "Model - User"],
            "configuration/seeder",
          ],
        },
        {
          title: "Usage",
          children: [
            "usage/concepts",
            "usage/events",
            "usage/middleware",
            "usage/soft-deleting",
            "usage/blade-templates",
          ],
        },
        "troubleshooting",
        "license",
        "contributing",
      ];
    case "7.x":
    case "6.x":
      return [
        "upgrade",
        "",
        "installation",
        {
          title: "The basics",
          children: [
            "the-basics/migrations",
            "the-basics/teams",
            ["the-basics/models/role", "Model - Role"],
            ["the-basics/models/permission", "Model - Permission"],
            ["the-basics/models/team", "Model - Team"],
            ["the-basics/models/user", "Model - User"],
          ],
        },
        {
          title: "Usage",
          children: [
            "usage/roles-and-permissions",
            "usage/querying-relationships",
            "usage/teams",
            "usage/objects-ownership",
            ["usage/multiple-users", "Multiple User Models"],
            "usage/events",
            "usage/middleware",
            "usage/soft-deleting",
            "usage/blade-templates",
            "usage/seeder",
            "usage/admin-panel",
          ],
        },
        "troubleshooting",
        "license",
        "contributing",
      ];
    case "8.x":
      return [
        "upgrade",
        "",
        "installation",
        {
          title: "The basics",
          children: [
            "the-basics/migrations",
            "the-basics/teams",
            ["the-basics/models/role", "Model - Role"],
            ["the-basics/models/permission", "Model - Permission"],
            ["the-basics/models/team", "Model - Team"],
            ["the-basics/models/user", "Model - User"],
          ],
        },
        {
          title: "Usage",
          children: [
            "usage/roles-and-permissions",
            "usage/querying-relationships",
            "usage/teams",
            ["usage/multiple-users", "Multiple User Models"],
            "usage/events",
            "usage/middleware",
            "usage/soft-deleting",
            "usage/blade-templates",
            "usage/seeder",
            "usage/admin-panel",
          ],
        },
        "troubleshooting",
        "license",
        "contributing",
      ];
  }
}

function getVersionsLinks(preLink = "docs") {
  let links = [
    { text: "8.x", link: `/${preLink}/8.x/` },
    { text: "7.x", link: `/${preLink}/7.x/` },
    { text: "6.x", link: `/${preLink}/6.x/` },
    { text: "5.2", link: `/${preLink}/5.2/` },
    { text: "5.1", link: `/${preLink}/5.1/` },
    { text: "5.0", link: `/${preLink}/5.0/` },
  ].sort((a, b) => a.text < b.text);

  if (preLink == "docs") {
    links.push({ text: "<5.0", link: "https://laratrust.readthedocs.io/" });
  }

  return links;
}

function getActiveVersion() {
  return getVersionsLinks().find((item) => item.text == ACTIVE_VERSION);
}

module.exports = {
  getDocsNavBar,
  getVersionsLinks,
  getActiveVersion,
};
