import {Component} from 'mithril-utilities';

export default class Footer extends Component {
  // noinspection JSMethodCanBeStatic
  view() {
    return (
      <footer>
        {this.leftFooter()}
        <div class="right-footer">
          {this.rightFooter()}
        </div>
      </footer>
    );
  }

  leftFooter() {
    return (
      <a href="https://openstamanager.com">{__('OpenSTAManager')}</a>
    );
  }

  rightFooter() {
    return (
      <>
        <strong>{__('Versione')}</strong> {app.VERSION}&nbsp;
        <small>(<code>{app.REVISION}</code>)</small>
      </>
    );
  }
}
