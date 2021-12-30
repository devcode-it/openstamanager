function readPackage(pkg) {
  /** @type {object} */
  pkg.dependencies = {
    ...pkg.peerDependencies,
    ...pkg.dependencies,
  }
  pkg.peerDependencies = {};

  return pkg;
}

module.exports = {
  hooks: {
    readPackage,
  },
};
