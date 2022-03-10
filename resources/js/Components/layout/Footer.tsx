import Component from '../Component';

export default class Footer extends Component {
  view() {
    return (
      <footer className={theme === 'high-contrast' ? 'mdc-high-contrast' : undefined}>
        <div class="left-footer">
            <span>
                <a href="https://openstamanager.com">
                    {__('OpenSTAManager')}
                </a>
            </span>
        </div>
        <div class="right-footer">
          <strong>{__('Versione')}</strong> {VERSION}&nbsp;
          <small>(<code>{REVISION}</code>)</small>
        </div>
      </footer>
    );
  }
}
