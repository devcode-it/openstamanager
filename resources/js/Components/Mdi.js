import Component from './Component';

export default class Mdi extends Component {
  view(vnode) {
    return <i {...vnode.attrs} class={`mdi mdi-${vnode.attrs.icon} ${vnode.attrs.class ?? ''}`} />;
    /*
    Quando MWC supporterà pienamente le icone SVG potremo
    import * as mdi from '@mdi/js';
    import {camelCase} from 'lodash/string';
    return <svg class={`mdi ${vnode.attrs.class ?? ''}`} {...vnode.attrs} viewBox={vnode.attrs.viewBox ?? '0 0 24 24'}>
      <path d={vnode.attrs.icon ? mdi[camelCase(`mdi-${vnode.attrs.icon}`)] : ''} />
    </svg>; */
  }
}
