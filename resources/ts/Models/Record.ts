import {Attr, Model, SpraypaintBase} from 'spraypaint';

@Model()
export default class Record extends SpraypaintBase {
  static baseUrl = '';
  static apiNamespace = '/api/restify';
  static clientApplication = 'OpenSTAManager';

  @Attr({persist: false}) createdAt!: string;
  @Attr({persist: false}) updatedAt!: string;

  isNew(): boolean {
    return this.id === undefined;
  }
}
