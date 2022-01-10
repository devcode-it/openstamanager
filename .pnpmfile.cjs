function readPackage(pkg) {
  const pkgs = ['@openstamanager/vite-config', '@maicol07/eslint-config'];

  if (pkgs.includes(pkg.name)) {
    /** @type {object} */
    pkg.dependencies = {
      ...pkg.peerDependencies,
      ...pkg.dependencies,
    }
    pkg.peerDependencies = {};
  }

  return pkg;
}

module.exports = {
  hooks: {
    readPackage,
  },
};
