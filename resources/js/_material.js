import '@material/mwc-button';
import '@material/mwc-drawer';
import '@material/mwc-icon-button';
import '@material/mwc-list';
import '@material/mwc-menu';
import './WebComponents/TopAppBar';
import './WebComponents/Drawer';

const drawer = document.getElementsByTagName('material-drawer')[0];
if (drawer) {
  const container = $(drawer).parent();
  container.on('MDCTopAppBar:nav', () => {
    drawer.open = !drawer.open;
  });
}
