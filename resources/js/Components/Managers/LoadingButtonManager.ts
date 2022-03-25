import {Button} from '@material/mwc-button';
import {CircularProgressManager} from '@osm/Components/Managers';

export class LoadingButtonManager extends CircularProgressManager {
  static selector = 'mwc-button[data-component-type="loading-button"]';

  constructor(private button: Button) {
    const loading = button.querySelector('mwc-circular-progress');
    if (loading) {
      super(loading);
    }
  }
}
