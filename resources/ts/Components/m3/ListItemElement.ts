import autoAnimate from '@formkit/auto-animate';
import {ListItemEl as MdListItem} from '@material/web/list/lib/listitem/list-item';
import {styles} from '@material/web/list/lib/listitem/list-item-styles.css.js';
import {ARIARole} from '@material/web/types/aria';
import {
  html,
  TemplateResult
} from 'lit';
import {
  property,
  queryAsync
} from 'lit/decorators.js';

export default class ListItemElement extends MdListItem {
  static override styles = [styles];
  @property({type: Boolean}) showSupportingText = false;
  @property({type: Boolean}) showMultiLineSupportingText = false;
  @queryAsync('.body') body!: Promise<HTMLDivElement>;

  // @ts-expect-error - Workaround for https://github.com/material-components/material-web/issues/3804
  // eslint-disable-next-line unicorn/no-null
  override role: ARIARole | null = null;

  async connectedCallback() {
    super.connectedCallback();
    // Auto-animate the body when it is rendered.
    autoAnimate(await this.body);
    this.role = 'menuitem';
  }

  /**
   * Handles rendering the headline and supporting text.
   */
  protected renderBody(): TemplateResult {
    return html`
      <div class="body">
          <span class="label-text">${this.headline}</span>
          ${this.showSupportingText ? this.renderSupportingText() : ''}
          ${this.showMultiLineSupportingText ? this.renderMultiLineSupportingText() : ''}
      </div>
    `;
  }

  /**
   * Renders the one-line supporting text.
   */
  protected renderSupportingText(): TemplateResult {
    return html`
      <span class="supporting-text">
            <slot name="supportingText">${this.supportingText}</slot>
        </span>
    `;
  }

  protected renderMultiLineSupportingText(): TemplateResult {
    return html`
        <span class="supporting-text supporting-text--multi-line">
          <slot name="multilineSupportingText">${this.multiLineSupportingText}</slot>
      </span>
    `;
  }
}
