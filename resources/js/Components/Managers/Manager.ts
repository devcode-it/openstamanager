export abstract class Manager {
  abstract static selector: string;
  static filter: (element: HTMLElement) => boolean = () => true;
}
