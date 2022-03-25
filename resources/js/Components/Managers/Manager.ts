export type ComponentManager =
  {new(element: any | HTMLElement): Manager}
  & Omit<typeof Manager, 'new'>;

export abstract class Manager {
  static selector: string;
  static filter: (element: any | HTMLElement) => boolean = () => true;

  protected constructor(protected element: HTMLElement) {
  }
}
