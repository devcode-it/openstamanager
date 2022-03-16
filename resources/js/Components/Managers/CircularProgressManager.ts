import {CircularProgress} from '@material/mwc-circular-progress';

import {Manager} from './Manager';

export class CircularProgressManager extends Manager {
  static selector = 'mwc-circular-progress';
  static filter = (element: CircularProgress) => element.closest('mwc-button').type !== 'loading-button';

  constructor(private loading: CircularProgress) {
  }

  public show() {
    this.loading.open();
  }

  public hide() {
    this.loading.close();
  }

  public get indeterminate(): boolean {
    return this.loading.indeterminate;
  }

  public set indeterminate(value: boolean) {
    this.loading.indeterminate = value;
  }

  public get progress(): number {
    return this.loading.progress;
  }

  public set progress(value: number) {
    this.loading.progress = value;
  }
}
