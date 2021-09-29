// eslint-disable-next-line import/no-commonjs,unicorn/prefer-module
module.exports = function (api) {
  api.assertVersion(7);
  api.cache.forever();

  return {
    presets: [
      [
        '@adeira/babel-preset-adeira',
        {
          target: 'js-esm'
        }
      ]
    ]
  };
};
