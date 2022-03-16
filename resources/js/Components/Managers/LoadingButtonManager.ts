import {Button} from '@material/mwc-button';
import {CircularProgress} from '@material/mwc-circular-progress';
import {CircularProgressManager} from '@osm/Components/Managers';

export class LoadingButtonManager extends CircularProgressManager {
  static selector = 'mwc-button[type="loading-button"]';

  private loading: CircularProgress;

  constructor(private button: Button) {
    this.loading = button.querySelector('mwc-circular-progress');
  }
}
