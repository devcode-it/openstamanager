import {MaterialIcons} from '../typings';
import Component from './Component';

type Attributes = {
  icon?: MaterialIcons
};

export default class Mdi extends Component<Attributes> {
  view() {
    this.attrs.addClassNames('mdi', `mdi-${this.attrs.pull('icon') as string}`);
    return <i {...this.attrs.all()} />;
  }
}
/**
 Quando MWC supporterà pienamente le icone SVG si potrà fare così:
 import * as mdi from '@mdi/js';
 import {camelCase} from 'lodash-es';

 return <svg class={`mdi ${vnode.attrs.class ?? ''}`}
 {...vnode.attrs} viewBox={vnode.attrs.viewBox ?? '0 0 24 24'}>
 <path d={vnode.attrs.icon ? mdi[camelCase(`mdi-${vnode.attrs.icon}`)] : ''} />
 </svg>;

 @see https://github.com/material-components/material-web/issues/1812
 */
