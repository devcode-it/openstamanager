import Component from './Component.jsx';

export default class Mdi extends Component {
  view(vnode) {
    this.attrs.addClassNames('mdi', `mdi-${vnode.attrs.icon}`);
    return <i {...this.attrs.all()} />;
  }
}

/**
 Quando MWC supporterà pienamente le icone SVG si potrà fare così:
 import * as mdi from '@mdi/js';
 import {camelCase} from 'lodash-es/string';

 return <svg class={`mdi ${vnode.attrs.class ?? ''}`}
 {...vnode.attrs} viewBox={vnode.attrs.viewBox ?? '0 0 24 24'}>
 <path d={vnode.attrs.icon ? mdi[camelCase(`mdi-${vnode.attrs.icon}`)] : ''} />
 </svg>;

 @see https://github.com/material-components/material-web/issues/1812
 */
