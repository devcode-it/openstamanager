import Drawer from '@osm/Components/layout/Drawer';
import Footer from '@osm/Components/layout/Footer';
import TopAppBar from '@osm/Components/layout/TopAppBar';
import {isMobile} from '@osm/utils/misc';
import {Children, Vnode} from 'mithril';
import Stream from 'mithril/stream';
import {Attributes, Component} from 'mithril-utilities';

export interface ScaffoldAttributes extends Attributes {

}

export default class Scaffold<A extends ScaffoldAttributes = ScaffoldAttributes> extends Component<A> {
  drawerOpen = Stream(!isMobile());

  view(vnode: Vnode<A, this>): Children {
    return (
      <Drawer open={this.drawerOpen}>
        <TopAppBar slot="top-app-bar" drawer-open-state={this.drawerOpen}/>

        <div slot="app-content">
          <main id="appContent">
            {vnode.children}
          </main>
          <Footer/>
        </div>
      </Drawer>
    );
  }
}
