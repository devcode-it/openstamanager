import '@material/mwc-dialog';

import {type Cash} from 'cash-dom/dist/cash';
import {uniqueId} from 'lodash-es';
import Lottie from 'lottie-web';
import {type ClassComponent} from 'mithril';

import Component from './Component.jsx';

export default class Alert extends Component implements ClassComponent<{
  heading?: string,
  icon?: string,
  image?: string,
  'image-width'?: string | number,
  'image-height'?: string | number,
  'image-alt'?: string,
  trigger?: string,
  open?: boolean
}> {
  view(vnode) {
    const image = {
      src: this.attrs.pull('image'),
      width: this.attrs.pull('image-width', '125px'),
      height: this.attrs.pull('image-height', '125px'),
      alt: this.attrs.pull('image-alt')
    };

    const actions = [];
    for (const child of vnode.children) {
      if (child.attrs && child.attrs.slot && ['primaryAction', 'secondaryAction'].includes(child.attrs.slot)) {
        actions.push(child);
        const index = vnode.children.indexOf(child);
        vnode.children.splice(index, 1);
      }
    }

    return (
      <mwc-dialog {...this.attrs.all()}>
        <div className="graphic" style={`width: ${image.width}; height: ${image.height}; margin: 0 auto;`}>
          {image.src && <img src={image.src} alt={image.alt}/>}
        </div>

        <div className="content">
          {vnode.children}
        </div>

        {actions.length > 0 ? actions : <mwc-button label={__('OK')} slot="primaryAction" dialogAction="ok"/>}
      </mwc-dialog>
    );
  }

  oninit(vnode) {
    super.oninit(vnode);
    if (this.attrs.get('id')) {
      this.attrs.put('id', uniqueId('dialog_'));
    }
  }

  oncreate(vnode) {
    const dialog: Cash = $(`#${this.attrs.get('id')}`);

    if (this.attrs.has('icon')) {
      const animation = Lottie.loadAnimation({
        container: dialog.find('.graphic')[0],
        renderer: 'svg',
        loop: false,
        autoplay: false,
        path: new URL(`/animations/${this.attrs.pull('icon')}.json`, import.meta.url).href
      });

      dialog.on('opening', () => {
        animation.goToAndStop(0);
      });

      dialog.on('opened', () => {
        animation.play();
      });
    }
  }
}
