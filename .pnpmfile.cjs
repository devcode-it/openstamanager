function readPackage(pkg) {
  const pkgs = ['@openstamanager/vite-config'];
  if (pkg.name in pkgs) {
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
