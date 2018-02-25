UPDATE `zz_widgets` SET `query` = REPLACE(`query`, '"', '''');
UPDATE `zz_modules` SET `options` = REPLACE(`options`, '"', ''''), `options2` = REPLACE(`options2`, '"', '''');
UPDATE `zz_group_module` SET `clause` = REPLACE(`clause`, '"', '''');
UPDATE `zz_views` SET `query` = REPLACE(`query`, '"', '''');
