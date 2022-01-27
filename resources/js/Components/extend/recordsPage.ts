import {deepmerge} from 'deepmerge-ts';

import {
  ColumnT,
  RecordsPage,
  SectionsT
} from '../Pages';
import {extend} from './extend';

/**
 * Adds or updates the columns of a RecordsPage
 *
 * @param page The page of the columns to add or update
 * @param columns An object containing the columns to add or update ({id: 'Heading' | {…}})
 */
export function updateColumns(
  page: RecordsPage & {prototype: RecordsPage},
  columns: Record<string, string | ColumnT>
) {
  extend(page.prototype, 'oninit', function (this: RecordsPage) {
    this.columns = deepmerge(this.columns, columns);
  });
}

/**
 * Deletes the columns of a RecordsPage
 *
 * @param page The page of the columns to delete
 * @param ids The IDs of the columns to delete
 */
export function deleteColumns(page: RecordsPage & {prototype: RecordsPage}, ids: string[]) {
  extend(page.prototype, 'oninit', function (this: RecordsPage) {
    for (const id of ids) {
      delete this.columns[id];
    }
  });
}

/**
 * Adds or updates the fields of a RecordsPage record dialog
 *
 * @param page The page of the fields to add or update
 * @param sections The new section
 */
export function updateFieldsSection(
  page: RecordsPage & {prototype: RecordsPage},
  sections: SectionsT
) {
  extend(page.prototype, 'oninit', function (this: RecordsPage) {
    this.sections = deepmerge(this.sections, sections);
  });
}

/**
 * Deletes the sections of a RecordsPage record dialog
 *
 * @param page The page of the sections to delete
 * @param sections The IDs of the dialog sections to delete.
 */
export function deleteSections(
  page: RecordsPage & {prototype: RecordsPage},
  sections: string[]
) {
  extend(page.prototype, 'oninit', function (this: RecordsPage) {
    for (const id of sections) {
      delete this.sections[id];
    }
  });
}


/**
 * Delets the fields of a RecordsPage record dialog
 *
 * @param page The page of the fields to delete
 * @param section The dialog section to delete
 * @param fields The IDs of the fields to delete
 */
export function deleteFields(
  page: RecordsPage & {prototype: RecordsPage},
  section: string,
  fields: string[]
) {
  extend(page.prototype, 'oninit', function (this: RecordsPage) {
    for (const id of fields) {
      delete this.sections[section].fields[id];
    }
  });
}
