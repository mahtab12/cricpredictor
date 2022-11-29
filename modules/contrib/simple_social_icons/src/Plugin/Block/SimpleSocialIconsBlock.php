<?php

namespace Drupal\simple_social_icons\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SimpleSocialIconsBlock' block.
 *
 * @Block(
 *  id = "simple_social_icons_block",
 *  admin_label = @Translation("Simple social icons block"),
 * )
 */
class SimpleSocialIconsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Controller\TitleResolverInterface definition.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Default Configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->requestStack = $container->get('request_stack');
    $instance->titleResolver = $container->get('title_resolver');
    $instance->configFactory = $container->get('config.factory');
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $reddit_enable = $this->configuration['reddit_enable'] ?? '';
    $tumblr_enable = $this->configuration['tumblr_enable'] ?? '';
    $linkedin_enable = $this->configuration['linkedin_enable'] ?? '';
    $pinterest_enable = $this->configuration['pinterest_enable'] ?? '';
    $email_enable = $this->configuration['email_enable'] ?? '';
    $facebook_enable = $this->configuration['facebook_enable'] ?? '';
    $twitter_enable = $this->configuration['twitter_enable'] ?? '';
    $reddit_icon_order = $this->configuration['reddit_icon_order'] ?? 1;
    $tumblr_icon_order = $this->configuration['tumblr_icon_order'] ?? 2;
    $twitter_icon_order = $this->configuration['twitter_icon_order'] ?? 3;
    $linkedin_icon_order = $this->configuration['linkedin_icon_order'] ?? 4;
    $pinterest_icon_order = $this->configuration['pinterest_icon_order'] ?? 5;
    $email_icon_order = $this->configuration['email_icon_order'] ?? 6;
    $facebook_icon_order = $this->configuration['facebook_icon_order'] ?? 7;

    $values = [
      'reddit_enable' => $reddit_enable,
      'tumblr_enable' => $tumblr_enable,
      'linkedin_enable' => $linkedin_enable,
      'pinterest_enable' => $pinterest_enable,
      'email_enable' => $email_enable,
      'facebook_enable' => $facebook_enable,
      'twitter_enable' => $twitter_enable,
      'reddit_icon_order' => $reddit_icon_order,
      'tumblr_icon_order' => $tumblr_icon_order,
      'twitter_icon_order' => $twitter_icon_order,
      'linkedin_icon_order' => $linkedin_icon_order,
      'pinterest_icon_order' => $pinterest_icon_order,
      'email_icon_order' => $email_icon_order,
      'facebook_icon_order' => $facebook_icon_order,
      'icon_placement' => $this->configuration['icon_placement'] ?? '',
      'reddit_titletext' => $this->configuration['reddit_titletext'] ?? '',
      'tumblr_titletext' => $this->configuration['tumblr_titletext'] ?? '',
      'twitter_titletext' => $this->configuration['twitter_titletext'] ?? '',
      'linkedin_titletext' => $this->configuration['linkedin_titletext'] ?? '',
      'email_titletext' => $this->configuration['email_titletext'] ?? '',
      'facebook_titletext' => $this->configuration['facebook_titletext'] ?? '',
      'pinterest_titletext' => $this->configuration['pinterest_titletext'] ?? '',
    ];

    $form['icon_placement'] = [
      '#type' => 'radios',
      '#title' => $this->t('Icon alignment'),
      '#default_value' => $this->configuration['icon_placement'] ?? '',
      '#description' => $this->t('By default it will display horizontally .'),
      '#options' => [0 => $this->t('Horizontal'), 1 => $this->t('Vertical')],
      '#weight' => '0',
    ];
    $form['size'] = [
      '#type' => 'range',
      '#title' => $this->t('Layout Size'),
      '#description' => $this->t('Adjust the layout size.'),
      '#default_value' => $this->configuration['size'] ?? '38',
      '#weight' => '0',
    ];
    $form['font_size'] = [
      '#type' => 'range',
      '#title' => $this->t('Icon size'),
      '#description' => $this->t('Adjust the icon size.'),
      '#default_value' => $this->configuration['font_size'] ?? '23',
      '#weight' => '0',
    ];
    $form['radius'] = [
      '#type' => 'range',
      '#title' => $this->t('Radius'),
      '#description' => $this->t('Adjust the icon radius'),
      '#default_value' => $this->configuration['radius'] ?? '30',
      '#weight' => '0',
    ];
    $form['spacing'] = [
      '#type' => 'range',
      '#title' => $this->t('Spacing'),
      '#description' => $this->t('Adjust the Margin between icons.'),
      '#default_value' => $this->configuration['spacing'] ?? '8',
      '#weight' => '0',
      '#attributes' => [
      // 'onblur' => 'simple_social_share_block_live_changes(this)',
      // 'onclick' => 'simple_social_share_block_live_changes(this)'
      ],
    ];
    $form['button_link_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Layout color'),
      '#description' => $this->t('Color of a link'),
      '#default_value' => $this->configuration['button_link_color'] ?? 'auto',
      '#weight' => '0',
    ];
    $form['icon_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Icon Color'),
      '#description' => $this->t('Color of an icon'),
      '#default_value' => $this->configuration['icon_color'] ?? '#ffffff',
      '#weight' => '0',
    ];
    $form['use_default_style_ignore_colors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use default style & ignore colors.'),
      '#description' => '',
      '#default_value' => $this->configuration['use_default_style_ignore_colors'] ?? '1',
      '#weight' => '0',
    ];
    $form['contact_information'] = [
      '#markup' => $this->icons($values),
      '#weight' => '-100',
      '#title' => 'Preview',
    ];
    $form['#attached']['library'][] = 'simple_social_icons/simple_social_icons';
    $style = '.soc li a {
                /*size*/
                width: 38px;
                height: 38px;
                line-height: 38px;

                /*size 55%*/
                font-size: 20px;

                /*Radius*/
                -webkit-border-radius: 25px;
                -moz-border-radius: 25px;
                border-radius: 25px;

                /*Spacing*/
                margin-right: 7px;
    }';

    $style .= '.soc_ver li a {
                /*size*/
                width: 38px;
                height: 38px;
                line-height: 38px;

                /*size 55%*/
                font-size: 20px;

                /*Radius*/
                -webkit-border-radius: 25px;
                -moz-border-radius: 25px;
                border-radius: 25px;

                /*Spacing*/
                margin-right: 7px;
    }';

    $form['#attached']['html_head'][] = [
      // The data.
      [
        // The HTML tag to add, in this case a <script> tag.
        '#tag' => 'style',
        // The value of the HTML tag, here we want to end up with
        // <script>alert("Hello world!");</script>.
        '#value' => Markup::create($style),
      ],
      // A key, to make it possible to recognize this HTML <HEAD> element when
      // altering.
      'hello-world',
    ];

    $form['simple_social_icons_reddit'] = [
      '#type' => 'details',
      '#title' => $this->t('Reddit settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];
    $form['simple_social_icons_reddit']['reddit_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->configuration['reddit_title'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '0',
      '#description' => $this->t('Reddit title to be used while sharing.'),
    ];
    $form['simple_social_icons_reddit']['reddit_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Url'),
      '#default_value' => $this->configuration['reddit_url'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '0',
      '#description' => $this->t('Reddit url to use while sharing.'),
    ];
    $form['simple_social_icons_reddit']['reddit_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable reddit icon.'),
      '#description' => '',
      '#default_value' => $this->configuration['reddit_enable'] ?? 0,
      '#weight' => '1',
    ];
    $form['simple_social_icons_reddit']['reddit_icon_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Order of icon'),
      '#default_value' => $this->configuration['reddit_icon_order'] ?? 1,
      '#options' => [
        '1' => $this->t('First'),
        '2' => $this->t('Second', [], ['context' => 'date_order']),
        '3' => $this->t('Third'),
        '4' => $this->t('Fourth'),
        '5' => $this->t('Fifth'),
        '6' => $this->t('Sixth'),
        '7' => $this->t('Seventh'),
      ],
    ];
    $form['simple_social_icons_tumblr'] = [
      '#type' => 'details',
      '#title' => $this->t('Tumblr settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];
    $form['simple_social_icons_tumblr']['tumblr_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->configuration['tumblr_title'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '0',
      '#description' => $this->t('Tumblr title to be used while sharing.'),
    ];
    $form['simple_social_icons_tumblr']['tumblr_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Url'),
      '#default_value' => $this->configuration['tumblr_url'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '0',
      '#description' => $this->t('Tumblr url to use while sharing.'),
    ];
    $form['simple_social_icons_tumblr']['tumblr_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable tumblr icon.'),
      '#description' => '',
      '#default_value' => $this->configuration['tumblr_enable'] ?? 0,
      '#weight' => '1',
    ];
    $form['simple_social_icons_tumblr']['tumblr_icon_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Order of icon'),
      '#default_value' => $this->configuration['tumblr_icon_order'] ?? 2,
      '#options' => [
        '1' => $this->t('First'),
        '2' => $this->t('Second', [], ['context' => 'date_order']),
        '3' => $this->t('Third'),
        '4' => $this->t('Fourth'),
        '5' => $this->t('Fifth'),
        '6' => $this->t('Sixth'),
        '7' => $this->t('Seventh'),
      ],
    ];
    $form['simple_social_icons_twitter'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];
    $form['simple_social_icons_twitter']['twitter_via'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Via'),
      '#default_value' => $this->configuration['twitter_via'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '0',
      '#description' => $this->t('Twitter handle to use when sharing.'),
    ];
    $form['simple_social_icons_twitter']['twitter_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable twitter icon.'),
      '#description' => '',
      '#default_value' => $this->configuration['twitter_enable'] ?? 0,
      '#weight' => '1',
    ];

    $form['simple_social_icons_twitter']['twitter_icon_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Order of icon'),
      '#default_value' => $this->configuration['twitter_icon_order'] ?? 4,
      '#options' => [
        '1' => $this->t('First'),
        '2' => $this->t('Second', [], ['context' => 'date_order']),
        '3' => $this->t('Third'),
        '4' => $this->t('Fourth'),
        '5' => $this->t('Fifth'),
        '6' => $this->t('Sixth'),
        '7' => $this->t('Seventh'),
      ],
    ];
    $form['simple_social_icons_linkedin'] = [
      '#type' => 'details',
      '#title' => $this->t('Linkedin settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];
    $form['simple_social_icons_linkedin']['linkedin_summary'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Summary'),
      '#default_value' => $this->configuration['linkedin_summary'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '1',
      '#description' => $this->t('Provide summary for the linkedin post'),
    ];
    $form['simple_social_icons_linkedin']['linkedin_source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source'),
      '#default_value' => $this->configuration['linkedin_source'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '0',
      '#description' => $this->t('Provide the source url of the linkedin post'),
    ];
    $form['simple_social_icons_linkedin']['linkedin_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable linkedin icon.'),
      '#description' => '',
      '#default_value' => $this->configuration['linkedin_enable'] ?? 0,
      '#weight' => '2',
    ];
    $form['simple_social_icons_linkedin']['linkedin_icon_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Order of icon'),
      '#default_value' => $this->configuration['linkedin_icon_order'] ?? 5,
      '#options' => [
        '1' => $this->t('First'),
        '2' => $this->t('Second', [], ['context' => 'date_order']),
        '3' => $this->t('Third'),
        '4' => $this->t('Fourth'),
        '5' => $this->t('Fifth'),
        '6' => $this->t('Sixth'),
        '7' => $this->t('Seventh'),
      ],
    ];

    $form['simple_social_icons_pinterest'] = [
      '#type' => 'details',
      '#title' => $this->t('Pinterest settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];
    $form['simple_social_icons_pinterest']['pinterest_image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image Url'),
      '#default_value' => $this->configuration['pinterest_image_url'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '0',
      '#description' => $this->t('Give the pinterest image url'),
    ];
    $form['simple_social_icons_pinterest']['pinterest_desc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['pinterest_desc'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '1',
      '#description' => $this->t('Give the description of the image'),
    ];
    $form['simple_social_icons_pinterest']['pinterest_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Pinterest icon.'),
      '#description' => '',
      '#default_value' => $this->configuration['pinterest_enable'] ?? 1,
      '#weight' => '2',
    ];

    $form['simple_social_icons_pinterest']['pinterest_icon_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Order of icon'),
      '#default_value' => $this->configuration['pinterest_icon_order'] ?? 6,
      '#options' => [
        '1' => $this->t('First'),
        '2' => $this->t('Second', [], ['context' => 'date_order']),
        '3' => $this->t('Third'),
        '4' => $this->t('Fourth'),
        '5' => $this->t('Fifth'),
        '6' => $this->t('Sixth'),
        '7' => $this->t('Seventh'),
      ],
    ];

    $form['simple_social_icons_email'] = [
      '#type' => 'details',
      '#title' => $this->t('Email settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];
    $form['simple_social_icons_email']['email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $this->configuration['email_subject'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
    ];
    $form['simple_social_icons_email']['email_body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#default_value' => $this->configuration['email_body'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
    ];
    $form['simple_social_icons_email']['email_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Email icon.'),
      '#description' => '',
      '#default_value' => $this->configuration['email_enable'] ?? 0,
      '#weight' => '2',
    ];
    $form['simple_social_icons_email']['email_icon_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Order of icon'),
      '#default_value' => $this->configuration['email_icon_order'] ?? 6,
      '#options' => [
        '1' => $this->t('First'),
        '2' => $this->t('Second', [], ['context' => 'date_order']),
        '3' => $this->t('Third'),
        '4' => $this->t('Fourth'),
        '5' => $this->t('Fifth'),
        '6' => $this->t('Sixth'),
        '7' => $this->t('Seventh'),
      ],
    ];

    $form['simple_social_icons_facebook'] = [
      '#type' => 'details',
      '#title' => $this->t('Facebook settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];
    $form['simple_social_icons_facebook']['facebook_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Facebook icon.'),
      '#description' => '',
      '#default_value' => $this->configuration['facebook_enable'] ?? 0,
      '#weight' => '2',
    ];
    $form['simple_social_icons_facebook']['facebook_icon_order'] = [
      '#type' => 'select',
      '#title' => $this->t('Order of icon'),
      '#default_value' => $this->configuration['facebook_icon_order'] ?? 7,
      '#options' => [
        '1' => $this->t('First'),
        '2' => $this->t('Second', [], ['context' => 'date_order']),
        '3' => $this->t('Third'),
        '4' => $this->t('Fourth'),
        '5' => $this->t('Fifth'),
        '6' => $this->t('Sixth'),
        '7' => $this->t('Seventh'),
      ],
    ];

    $form['simple_social_icons_titletext'] = [
      '#type' => 'details',
      '#title' => $this->t('Set title on mouse hovering of links'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#description' => '',
    ];

    $form['simple_social_icons_titletext']['tumblr_titletext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tumblr title text'),
      '#default_value' => $this->configuration['tumblr_titletext'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '1',
    ];

    $form['simple_social_icons_titletext']['reddit_titletext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reddit title text'),
      '#default_value' => $this->configuration['reddit_titletext'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '1',
    ];
    $form['simple_social_icons_titletext']['twitter_titletext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter title text'),
      '#default_value' => $this->configuration['twitter_titletext'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '1',
    ];
    $form['simple_social_icons_titletext']['linkedin_titletext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Linkedin title text'),
      '#default_value' => $this->configuration['linkedin_titletext'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '1',
    ];
    $form['simple_social_icons_titletext']['email_titletext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email title text'),
      '#default_value' => $this->configuration['email_titletext'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '1',
    ];
    $form['simple_social_icons_titletext']['facebook_titletext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook title text'),
      '#default_value' => $this->configuration['facebook_titletext'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '1',
    ];
    $form['simple_social_icons_titletext']['pinterest_titletext'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pinterest title text'),
      '#default_value' => $this->configuration['pinterest_titletext'] ?? '',
      '#size' => 60,
      '#maxlength' => 128,
      '#weight' => '1',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if (!$form_state->getValue('simple_social_icons_reddit')['reddit_enable']) {
      $order_icon['reddit_icon_order'] = $form_state->getValue('simple_social_icons_reddit')['reddit_icon_order'];
    }
    if (!$form_state->getValue('simple_social_icons_tumblr')['tumblr_enable']) {
      $order_icon['tumblr_icon_order'] = $form_state->getValue('simple_social_icons_tumblr')['tumblr_icon_order'];
    }
    if (!$form_state->getValue('simple_social_icons_twitter')['twitter_enable']) {
      $order_icon['twitter_icon_order'] = $form_state->getValue('simple_social_icons_twitter')['twitter_icon_order'];
    }
    if (!$form_state->getValue('simple_social_icons_linkedin')['linkedin_enable']) {
      $order_icon['linkedin_icon_order'] = $form_state->getValue('simple_social_icons_linkedin')['linkedin_icon_order'];
    }
    if (!$form_state->getValue('simple_social_icons_pinterest')['pinterest_enable']) {
      $order_icon['pinterest_icon_order'] = $form_state->getValue('simple_social_icons_pinterest')['pinterest_icon_order'];
    }
    if (!$form_state->getValue('simple_social_icons_email')['email_enable']) {
      $order_icon['email_icon_order'] = $form_state->getValue('simple_social_icons_email')['email_icon_order'];
    }
    if (!$form_state->getValue('simple_social_icons_facebook')['facebook_enable']) {
      $order_icon['facebook_icon_order'] = $form_state->getValue('simple_social_icons_facebook')['facebook_icon_order'];
    }
    $val = [];
    $previous_val = '';
    foreach ($order_icon as $key => $icon) {
      $keyarray = explode('_', $key);
      $array = array_reverse($keyarray);
      $key = ucfirst(end($array));
      if (!in_array($icon, $val)) {
        $val[] = $icon;
        $previous_val = $key;
      }
      else {
        $form_state->setErrorByName($key, $this->t('<b>NOTE: Each icon should have distinct order</b>'));
        $this->messenger()->addStatus($this->t('Order of icon is same for %previous_val and %key error',
          [
            '%previous_val' => $previous_val,
            '%key' => $key,
          ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->configuration['icon_placement'] = $form_state->getValue('icon_placement');
    $this->configuration['size'] = $form_state->getValue('size');
    $this->configuration['font_size'] = $form_state->getValue('font_size');
    $this->configuration['radius'] = $form_state->getValue('radius');
    $this->configuration['spacing'] = $form_state->getValue('spacing');
    $this->configuration['button_link_color'] = $form_state->getValue('button_link_color');
    $this->configuration['icon_color'] = $form_state->getValue('icon_color');
    $this->configuration['use_default_style_ignore_colors'] = $form_state->getValue('use_default_style_ignore_colors');

    $this->configuration['reddit_title'] = $form_state->getValue('simple_social_icons_reddit')['reddit_title'];
    $this->configuration['reddit_url'] = $form_state->getValue('simple_social_icons_reddit')['reddit_url'];
    $this->configuration['reddit_enable'] = $form_state->getValue('simple_social_icons_reddit')['reddit_enable'];
    $this->configuration['reddit_icon_order'] = $form_state->getValue('simple_social_icons_reddit')['reddit_icon_order'];

    $this->configuration['tumblr_title'] = $form_state->getValue('simple_social_icons_tumblr')['tumblr_title'];
    $this->configuration['tumblr_url'] = $form_state->getValue('simple_social_icons_tumblr')['tumblr_url'];
    $this->configuration['tumblr_enable'] = $form_state->getValue('simple_social_icons_tumblr')['tumblr_enable'];
    $this->configuration['tumblr_icon_order'] = $form_state->getValue('simple_social_icons_tumblr')['tumblr_icon_order'];

    $this->configuration['twitter_via'] = $form_state->getValue('simple_social_icons_twitter')['twitter_via'];
    $this->configuration['twitter_enable'] = $form_state->getValue('simple_social_icons_twitter')['twitter_enable'];
    // $this->configuration['twitter_weight'] =
    // $form_state->getValue('simple_social_icons_twitter')['twitter_weight'];.
    $this->configuration['twitter_icon_order'] = $form_state->getValue('simple_social_icons_twitter')['twitter_icon_order'];

    $this->configuration['linkedin_summary'] = $form_state->getValue('simple_social_icons_linkedin')['linkedin_summary'];
    $this->configuration['linkedin_source'] = $form_state->getValue('simple_social_icons_linkedin')['linkedin_source'];
    $this->configuration['linkedin_enable'] = $form_state->getValue('simple_social_icons_linkedin')['linkedin_enable'];
    $this->configuration['linkedin_icon_order'] = $form_state->getValue('simple_social_icons_linkedin')['linkedin_icon_order'];

    $this->configuration['pinterest_image_url'] = $form_state->getValue('simple_social_icons_pinterest')['pinterest_image_url'];
    $this->configuration['pinterest_desc'] = $form_state->getValue('simple_social_icons_pinterest')['pinterest_desc'];
    $this->configuration['pinterest_enable'] = $form_state->getValue('simple_social_icons_pinterest')['pinterest_enable'];
    $this->configuration['pinterest_icon_order'] = $form_state->getValue('simple_social_icons_pinterest')['pinterest_icon_order'];

    $this->configuration['email_subject'] = $form_state->getValue('simple_social_icons_email')['email_subject'];
    $this->configuration['email_body'] = $form_state->getValue('simple_social_icons_email')['email_body'];
    $this->configuration['email_enable'] = $form_state->getValue('simple_social_icons_email')['email_enable'];
    $this->configuration['email_icon_order'] = $form_state->getValue('simple_social_icons_email')['email_icon_order'];

    $this->configuration['facebook_enable'] = $form_state->getValue('simple_social_icons_facebook')['facebook_enable'];
    $this->configuration['facebook_icon_order'] = $form_state->getValue('simple_social_icons_facebook')['facebook_icon_order'];

    $this->configuration['tumblr_titletext'] = $form_state->getValue('simple_social_icons_titletext')['tumblr_titletext'];
    $this->configuration['reddit_titletext'] = $form_state->getValue('simple_social_icons_titletext')['reddit_titletext'];
    $this->configuration['twitter_titletext'] = $form_state->getValue('simple_social_icons_titletext')['twitter_titletext'];
    $this->configuration['linkedin_titletext'] = $form_state->getValue('simple_social_icons_titletext')['linkedin_titletext'];
    $this->configuration['email_titletext'] = $form_state->getValue('simple_social_icons_titletext')['email_titletext'];
    $this->configuration['facebook_titletext'] = $form_state->getValue('simple_social_icons_titletext')['facebook_titletext'];
    $this->configuration['pinterest_titletext'] = $form_state->getValue('simple_social_icons_titletext')['pinterest_titletext'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $icon_placement = $this->configuration['icon_placement'] ?? '';
    $size = ($this->configuration['size'] ?? '38') . 'px';
    $font_size = ($this->configuration['font_size'] ?? '22') . 'px';
    $radius = ($this->configuration['radius'] ?? '30') . 'px';
    $spacing = ($this->configuration['spacing'] ?? '8') . 'px';
    $button_link_color = $this->configuration['button_link_color'] ?? '#eee';
    $icon_color = $this->configuration['icon_color'] ?? '#fffff';
    $default_style = $this->configuration['use_default_style_ignore_colors'] ?? '';

    $reddit_url = $this->configuration['reddit_url'] ?? '';
    $reddit_title = $this->configuration['reddit_title'] ?? '';
    $reddit_enable = $this->configuration['reddit_enable'] ?? '';
    $reddit_icon_order = $this->configuration['reddit_icon_order'] ?? '1';

    $tumblr_url = $this->configuration['tumblr_url'] ?? '';
    $tumblr_title = $this->configuration['tumblr_title'] ?? '';
    $tumblr_enable = $this->configuration['tumblr_enable'] ?? '';
    $tumblr_icon_order = $this->configuration['tumblr_icon_order'] ?? '2';

    $twitter_via = $this->configuration['twitter_via'] ?? '';
    $twitter_enable = $this->configuration['twitter_enable'] ?? '';
    $twitter_icon_order = $this->configuration['twitter_icon_order'] ?? '4';

    $linkedin_summary = $this->configuration['linkedin_summary'] ?? '';
    $linkedin_source = $this->configuration['linkedin_source'] ?? '';
    $linkedin_enable = $this->configuration['linkedin_enable'] ?? '';
    $linkedin_icon_order = $this->configuration['linkedin_icon_order'] ?? '5';

    $pinterest_image_url = $this->configuration['pinterest_image_url'] ?? '';
    $pinterest_desc = $this->configuration['pinterest_desc'] ?? '';
    $pinterest_enable = $this->configuration['pinterest_enable'] ?? '';
    $pinterest_icon_order = $this->configuration['pinterest_icon_order'] ?? '6';

    $email_subject = $this->configuration['email_subject'] ?? '';
    $email_body = $this->configuration['email_body'] ?? '';
    $email_enable = $this->configuration['email_enable'] ?? '';
    $email_icon_order = $this->configuration['email_icon_order'] ?? '7';

    $facebook_enable = $this->configuration['facebook_enable'] ?? '';
    $facebook_icon_order = $this->configuration['facebook_icon_order'] ?? '8';

    if ($default_style) {
      $icon_color = '#ffffff';
      $button_link_color = 'none';
    }

    $output = [];
    // No cache.
    $output[]['#cache']['max-age'] = 0;
    $values = [
      'icon_placement' => $icon_placement,
      'reddit_title' => $reddit_title,
      'reddit_url' => $reddit_url,
      'reddit_enable' => $reddit_enable,
      'tumblr_title' => $tumblr_title,
      'tumblr_url' => $tumblr_url,
      'tumblr_enable' => $tumblr_enable,
      'twitter_via' => $twitter_via,
      'twitter_enable' => $twitter_enable,
      'linkedin_summary' => $linkedin_summary,
      'linkedin_source' => $linkedin_source,
      'linkedin_enable' => $linkedin_enable,
      'pinterest_image_url' => $pinterest_image_url,
      'pinterest_desc' => $pinterest_desc,
      'pinterest_enable' => $pinterest_enable,
      'email_subject' => $email_subject,
      'email_body' => $email_body,
      'email_enable' => $email_enable,
      'facebook_enable' => $facebook_enable,
      'reddit_icon_order' => $reddit_icon_order,
      'tumblr_icon_order' => $tumblr_icon_order,
      'twitter_icon_order' => $twitter_icon_order,
      'linkedin_icon_order' => $linkedin_icon_order,
      'pinterest_icon_order' => $pinterest_icon_order,
      'email_icon_order' => $email_icon_order,
      'facebook_icon_order' => $facebook_icon_order,
      'reddit_titletext' => $this->configuration['reddit_titletext'] ?? '',
      'tumblr_titletext' => $this->configuration['tumblr_titletext'] ?? '',
      'twitter_titletext' => $this->configuration['twitter_titletext'] ?? '',
      'linkedin_titletext' => $this->configuration['linkedin_titletext'] ?? '',
      'email_titletext' => $this->configuration['email_titletext'] ?? '',
      'facebook_titletext' => $this->configuration['facebook_titletext'] ?? '',
      'pinterest_titletext' => $this->configuration['pinterest_titletext'] ?? '',
    ];
    $output[] = ['#markup' => $this->icons($values)];

    $style = ".soc li a {
            /*size*/
            width: $size;
            height: $size;
            line-height: $size;

            /*size 55%*/
            font-size: $font_size;

            /*Radius*/
            -webkit-border-radius: $radius;
            -moz-border-radius: $radius;
            border-radius: $radius;

            /*Spacing*/
            margin-right: $spacing;
            color: $icon_color !important;
            background-color: $button_link_color !important;
             /*color: #ffffff;*/
             /*background-color: none;*/
    }";

    $style .= ".soc_ver li a {
            /*size*/
            width: $size;
            height: $size;
            line-height: $size;

            /*size 55%*/
            font-size: $font_size;

            /*Radius*/
            -webkit-border-radius: $radius;
            -moz-border-radius: $radius;
            border-radius: $radius;

            /*Spacing*/
            margin-right: $spacing;
            color: $icon_color !important;
            background-color: $button_link_color !important;
             /*color: #ffffff;*/
             /*background-color: none;*/
    }";

    $output['#attached']['html_head'][] = [
      // The data.
      [
        // The HTML tag to add, in this case a <script> tag.
        '#tag' => 'style',
        // The value of the HTML tag, here we want to end up with
        // <script>alert("Hello world!");</script>.
        '#value' => Markup::create($style),
      ],
      // A key, to make it possible to recognize this HTML <HEAD> element when
      // altering.
      'hello-world',
    ];

    $output['#attached']['library'][] = 'simple_social_icons/simple_social_icons';

    return $output;
  }

  /**
   * Get the Icons Value.
   *
   * @param array $values
   *   Icon array values.
   *
   * @return string
   *   Return string.
   */
  private function icons(array $values) {
    $request = $this->requestStack->getCurrentRequest();
    $url = Url::fromRoute('<current>');
    $scheme = $request->getScheme();
    $url = $request->getHttpHost() . $url->toString();

    // Load the current node.
    $node = $this->routeMatch->getParameter('node');

    $site_config = $this->configFactory->get('system.site');

    $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    if ($route) {
      $title = $this->titleResolver->getTitle($request, $route);
    }
    elseif ($node) {
      $node_fields = $node->toArray();
      $title = $node_fields['title'][0]['value'];
    }
    else {
      // Default title will come here.
      $title = $site_config->get('name');
    }

    if (!empty($values['twitter_via'])) {
      $twitter_icon = '<li><a class="soc-twitter soc_ver-twitter" href="https://twitter.com/intent/tweet?source=' . $url . '&text=' . $title . ':' . $url . '&via=' . $values['twitter_via'] . '" target="_blank" title=" ' . $values['twitter_titletext'] . ' "></a></li>';
    }
    else {
      $twitter_icon = '<li><a class="soc-twitter soc_ver-twitter" href="https://twitter.com/intent/tweet?source=' . $url . '&text=' . $title . ':' . $url . '&via=" target="_blank" title=" ' . $values['twitter_titletext'] . ' "></a></li>';
    }

    $facebook_icon = '<li><a class="soc-facebook soc_ver-facebook" href="https://www.facebook.com/sharer/sharer.php?u=' . $url . '&t=' . $title . '" target="_blank" title=" ' . $values['facebook_titletext'] . ' "></a></li>';

    if (!empty($values['pinterest_image_url']) && !empty($values['pinterest_desc'])) {
      $pinterest_icon = '<li><a target="_blank" title=" ' . $values['pinterest_titletext'] . ' " class="soc-pinterest soc_ver-pinterest" href="https://pinterest.com/pin/create/button/?url=' . $url . '&media=' . $values['pinterest_image_url'] . '&description=' . $values['pinterest_desc'] . '"></a></li>';
    }
    elseif (!empty($values['pinterest_image_url'])) {
      $pinterest_icon = '<li><a target="_blank" title=" ' . $values['pinterest_titletext'] . ' " class="soc-pinterest soc_ver-pinterest" href="https://pinterest.com/pin/create/button/?url=' . $url . '&media=' . $values['pinterest_image_url'] . '&description="></a></li>';
    }
    elseif (!empty($values['pinterest_desc'])) {
      $pinterest_icon = '<li><a target="_blank" title=" ' . $values['pinterest_titletext'] . ' " class="soc-pinterest soc_ver-pinterest" href="https://pinterest.com/pin/create/button/?url=' . $url . '&media=&description=' . $values['pinterest_desc'] . '"></a></li>';
    }
    else {
      $pinterest_icon = '<li><a target="_blank" title=" ' . $values['pinterest_titletext'] . ' " class="soc-pinterest soc_ver-pinterest" href="https://pinterest.com/pin/create/button/?url=' . $url . '&media=&description="></a></li>';
    }

    $linkedin_url = $scheme . '://' . $url;

    if (!empty($values['linkedin_summary']) && !empty($values['linkedin_source'])) {
      $linked_in_icon = '<li><a class="soc-linkedin" href="https://www.linkedin.com/shareArticle?mini=true&url=' . $linkedin_url . '&title=' . $title . '&source=' . $values['linkedin_source'] . '&summary=' . $values['linkedin_summary'] . '" target="_blank"></a></li>';
    }
    elseif (!empty($values['linkedin_source'])) {
      $linked_in_icon = '<li><a class="soc-linkedin" href="https://www.linkedin.com/shareArticle?mini=true&url=' . $linkedin_url . '&title=' . $title . '&source=' . $values['linkedin_source'] . '&summary=" target="_blank"></a></li>';
    }
    elseif (!empty($values['linkedin_summary'])) {
      $linked_in_icon = '<li><a class="soc-linkedin" href="https://www.linkedin.com/shareArticle?mini=true&url=' . $linkedin_url . '&title=' . $title . '&source=&summary=' . $values['linkedin_summary'] . '" target="_blank"></a></li>';
    }
    else {
      $linked_in_icon = '<li><a class="soc-linkedin" href="https://www.linkedin.com/shareArticle?mini=true&url=' . $linkedin_url . '&title=' . $title . '&source=&summary=" target="_blank"></a></li>';
    }

    // $blog_icon = '<li><a class="soc-rss soc-icon-last" href="#"></a></li>';.
    if (!empty($values['email_body']) && !empty($values['email_subject'])) {
      $email = '<li><a title=" ' . $values['email_titletext'] . '" class="soc-email1 soc_ver-email1" href="mailto:?&subject=' . $values['email_subject'] . '&body=' . $values['email_body'] . ' ' . $url . '"></a></li>';
    }
    elseif (!empty($values['email_body'])) {
      $email = '<li><a title=" ' . $values['email_titletext'] . '" class="soc-email1 soc_ver-email1" href="mailto:?&subject=&body=' . $values['email_body'] . ' ' . $url . '"></a></li>';
    }
    elseif (!empty($values['email_subject'])) {
      $email = '<li><a title=" ' . $values['email_titletext'] . '" class="soc-email1 soc_ver-email1" href="mailto:?&subject=' . $values['email_subject'] . '&body=' . $url . '"></a></li>';
    }
    else {
      $email = '<li><a title=" ' . $values['email_titletext'] . '" class="soc-email1 soc_ver-email1" href="mailto:?&subject=&body=' . $url . '"></a></li>';
    }

    if (!empty($values['tumblr_url']) && !empty($values['tumblr_title'])) {
      $tumblr_icon = '<li><a target="_blank" title=" ' . $values['tumblr_titletext'] . '" class="soc-tumblr soc_ver-tumblr" href="http://www.tumblr.com/share?v=3&u=' . $values['tumblr_url'] . '&t=' . $values['tumblr_title'] . '"></a></li>';
    }
    elseif (!empty($values['tumblr_url'])) {
      $tumblr_icon = '<li><a target="_blank" title=" ' . $values['tumblr_titletext'] . '" class="soc-tumblr soc_ver-tumblr" href="http://www.tumblr.com/share?v=3&u=' . $values['tumblr_url'] . '&t=' . $title . '"></a></li>';
    }
    elseif (!empty($values['tumblr_title'])) {
      $tumblr_icon = '<li><a target="_blank" title=" ' . $values['tumblr_titletext'] . '" class="soc-tumblr soc_ver-tumblr" href="http://www.tumblr.com/share?v=3&u=' . $url . '&t=' . $values['tumblr_title'] . '"></a></li>';
    }
    else {
      $tumblr_icon = '<li><a target="_blank" title=" ' . $values['tumblr_titletext'] . '" class="soc-tumblr soc_ver-tumblr" href="http://www.tumblr.com/share?v=3&u=' . $url . '&t=' . $title . '"></a></li>';
    }

    if (!empty($values['reddit_url']) && !empty($values['reddit_title'])) {
      $reddit_icon = '<li><a target="_blank" title=" ' . $values['reddit_titletext'] . '" class="soc-reddit soc_ver-reddit" href="http://www.reddit.com/submit?url=' . $values['reddit_url'] . '&title=' . $values['reddit_title'] . '"></a></li>';
    }
    elseif (!empty($values['reddit_url'])) {
      $reddit_icon = '<li><a target="_blank" title=" ' . $values['reddit_titletext'] . '" class="soc-reddit soc_ver-reddit" href="http://www.reddit.com/submit?url=' . $values['reddit_url'] . '&title=' . $title . '"></a></li>';
    }
    elseif (!empty($values['reddit_title'])) {
      $reddit_icon = '<li><a target="_blank" title=" ' . $values['reddit_titletext'] . '" class="soc-reddit soc_ver-reddit" href="http://www.reddit.com/submit?url=' . $url . '&title=' . $values['reddit_title'] . '"></a></li>';
    }
    else {
      $reddit_icon = '<li><a target="_blank" title=" ' . $values['reddit_titletext'] . '" class="soc-reddit soc_ver-reddit" href="http://www.reddit.com/submit?url=' . $url . '&title=' . $title . '"></a></li>';
    }

    if ($values['icon_placement'] == 0) {
      /* icons are displaying horizontal value as 0 */
      if (!$values['reddit_enable']) {
        $icon_order[$values['reddit_icon_order']] = $reddit_icon;
      }
      if (!$values['tumblr_enable']) {
        $icon_order[$values['tumblr_icon_order']] = $tumblr_icon;
      }
      if (!$values['twitter_enable']) {
        $icon_order[$values['twitter_icon_order']] = $twitter_icon;
      }
      if (!$values['linkedin_enable']) {
        $icon_order[$values['linkedin_icon_order']] = $linked_in_icon;
      }
      if (!$values['pinterest_enable']) {
        $icon_order[$values['pinterest_icon_order']] = $pinterest_icon;
      }
      if (!$values['email_enable']) {
        $icon_order[$values['email_icon_order']] = $email;
      }
      if (!$values['facebook_enable']) {
        $icon_order[$values['facebook_icon_order']] = $facebook_icon;
      }

      // $icons .= '</ul>';
      // dsm($val);
      ksort($icon_order);
      $icons = '<ul class="soc">';
      foreach ($icon_order as $icon_sort_list) {
        $icons .= $icon_sort_list;
        // dsm($val_new[$key]);.
      }
      $icons .= '</ul>';
      return $icons;
    }
    else {
      /* icons are displaying vertical value as 1 */
      $icons = '<ul class="soc_ver">';
      if (!$values['reddit_enable']) {
        // $icons .= $reddit_icon;.
        $icon_order[$values['reddit_icon_order']] = $reddit_icon;
      }
      if (!$values['tumblr_enable']) {
        // $icons .= $tumblr_icon;.
        $icon_order[$values['tumblr_icon_order']] = $tumblr_icon;
      }
      if (!$values['twitter_enable']) {
        // $icons .= $twitter_icon;.
        $icon_order[$values['twitter_icon_order']] = $twitter_icon;
      }
      if (!$values['linkedin_enable']) {
        // $icons .= $linked_in_icon;.
        $icon_order[$values['linkedin_icon_order']] = $linked_in_icon;
      }
      if (!$values['pinterest_enable']) {
        // $icons .= $pinterest_icon;.
        $icon_order[$values['pinterest_icon_order']] = $pinterest_icon;
      }
      if (!$values['email_enable']) {
        // $icons .= $email;.
        $icon_order[$values['email_icon_order']] = $email;
      }
      if (!$values['facebook_enable']) {
        // $icons .= $facebook_icon;.
        $icon_order[$values['facebook_icon_order']] = $facebook_icon;
      }

      ksort($icon_order);
      $icons = '<ul class="soc_ver">';
      foreach ($icon_order as $icon_sort_list) {
        $icons .= $icon_sort_list;
      }
      $icons .= '</ul>';
      return $icons;
    }
  }

}
