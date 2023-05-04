import {Component} from 'mithril-utilities';

export default class Footer extends Component {
  // noinspection JSMethodCanBeStatic
  view() {
    return (
      <footer>
        <a href="https://openstamanager.com">{__('OpenSTAManager')}</a>
        <div class="right-footer">
          <strong>{__('Versione')}</strong> {app.VERSION}&nbsp;
          <small>(<code>{app.REVISION}</code>)</small>
        </div>
      </footer>
    );
  }
}
