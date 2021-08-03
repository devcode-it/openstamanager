import Component from './Component';

/**
 * The `Page` component
 *
 * @abstract
 */
export default class Page extends Component {
  page = JSON.parse($('#app').attr('data-page'));
}
