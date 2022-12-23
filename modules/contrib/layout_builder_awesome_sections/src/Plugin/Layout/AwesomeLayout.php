<?php

namespace Drupal\layout_builder_awesome_sections\Plugin\Layout;

use CssLint\Linter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\file\Entity\File;
use Mexitek\PHPColors\Color;

/**
 * Class AwesomeLayout.
 *
 */
class AwesomeLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    /** @var \Drupal\Core\Layout\LayoutDefinition $plugin_definition */
    $plugin_definition = $this->getPluginDefinition();
    $regions = $plugin_definition->getRegions();
    $region_count = count($regions);
    $configuration['uuid'] = NULL;
    // Default column width configuration.
    if ($region_count > 1) {
      $column_width = [];
      foreach ($regions as $key => $region) {
        $column_width[$key] = round(1 / $region_count, 2) * 100;
      }
      if ($column_width == 3) {
        $column_width[array_keys($regions)[1]] = 34;
      }
      $configuration['section_properties']['column_width'] = $column_width;

      $configuration['section_properties']['half_gutter'] = NULL;
      // Default mobile_breakpoint width configuration.
      $configuration['section_properties']['mobile_breakpoint'] = 576;
      $configuration['section_properties']['background_image'] = [];
      $configuration['section_properties']['background_color'] = '#ffffff';
    }

    // Default section_class width configuration.
    $configuration['section_properties']['section_class'] = '';

    $region_properties = [];
    foreach ($regions as $key => $region) {
      $region_properties[$key] = [];
      $region_properties[$key]['background_color'] = '#ffffff';
      $region_properties[$key]['background_color_opacity'] = 1;
      $region_properties[$key]['background_color_rgba'] = NULL;
      $region_properties[$key]['padding_left'] = NULL;
      $region_properties[$key]['padding_right'] = NULL;
      $region_properties[$key]['padding_top'] = NULL;
      $region_properties[$key]['padding_bottom'] = NULL;
      $region_properties[$key]['background_image'] = [];
      $region_properties[$key]['display_background_image'] = FALSE;
      $region_properties[$key]['display_background_color'] = FALSE;
      $region_properties[$key]['class'] = '';
      $region_properties[$key]['style'] = NULL;
      $region_properties[$key]['mobile_style'] = NULL;
      $region_properties[$key]['min-height'] = NULL;
      $region_properties[$key]['vertical_align'] = NULL;
    }
    $configuration = $configuration + $region_properties;

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    /** @var \Drupal\Core\Layout\LayoutDefinition $plugin_definition */
    $plugin_definition = $this->getPluginDefinition();
    $regions = $plugin_definition->getRegions();
    foreach ($regions as $key => $region) {
      if (isset($values[$key]['style'])) {
        $cssLinter = new Linter();
        $style_validity = $cssLinter->lintString('.selector {' . $values[$key]['style'] . '}');
        if (!$style_validity) {
          $form_state->setError($form['block_attributes']['style'], $this->t('Inline styles must be valid CSS'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Layout\LayoutDefinition $plugin_definition */
    $plugin_definition = $this->getPluginDefinition();
    $regions = $plugin_definition->getRegions();
    $region_count = count($regions);

    // Section properties
    $form['section_properties'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this
        ->t('Section properties'),
    ];

    // Section properties: column width
    if ($region_count > 1) {

      foreach ($regions as $key => $region) {
        $form['section_properties']['column_width'][$key] = [
          '#type' => 'number',
          '#min' => 1,
          '#max' => 99,
          'step' => 1,
          '#title' => $this
            ->t('Region ' . $region['label'] . ' width (%)'),
          '#default_value' => $this->configuration['section_properties']['column_width'][$key],
        ];
      }
    }

    // Section properties: mobile Breakpoint
    if ($region_count > 1) {
      $form['section_properties']['mobile_breakpoint'] = [
        '#type' => 'number',
        '#title' => $this
          ->t('Mobile breakpoint width (px)'),
        '#min' => 1,
        '#max' => 2160,
        'step' => 1,
        '#default_value' => $this->configuration['section_properties']['mobile_breakpoint'],
      ];

      $form['section_properties']['half_gutter'] = [
        '#type' => 'number',
        '#title' => $this
          ->t('Half Gutter (px)'),
        '#description' => $this->t('Gutter will be twice of this value'),
        '#min' => 1,
        '#max' => 100,
        '#step' => 0.01,
        '#default_value' => $this->configuration['section_properties']['half_gutter'],
      ];
    }

    // Section properties: class
    $form['section_properties']['section_class'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('Section class'),
      '#default_value' => $this->configuration['section_properties']['section_class'],
      '#size' => 60,
      '#maxlength' => 128,
    ];

    $form['section_properties']['background_color'] = [
      '#type' => 'color',
      '#title' => $this
        ->t('Background color'),
      '#default_value' => $this->configuration['section_properties']['background_color'],
    ];

    $form['section_properties']['background_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Background Image'),
      '#upload_location' => 'public://layout_builder_awesome_sections/' . date("Y-m-d"),
      '#multiple' => FALSE,
      '#default_value' => $this->configuration['section_properties']['background_image'],
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg svg'],
        'file_validate_size' => '30M',
      ],
    ];

    // Section properties: class
    $form['section_properties']['style'] = [
      '#type' => 'textfield',
      '#title' => 'Style',
      '#description' => $this->t('Inline CSS styles. <em>In general, inline CSS styles should be avoided.</em>'),
      '#default_value' => $this->configuration['section_properties']['style'],
    ];

    // Section properties: class
    $form['section_properties']['mobile_style'] = [
      '#type' => 'textfield',
      '#title' => 'Mobile style',
      '#description' => $this->t('Inline CSS styles. <em>In general, inline CSS styles should be avoided.(Add !important if it override the same pc style)</em>'),
      '#default_value' => $this->configuration['section_properties']['mobile_style'],
    ];

    // Each region properties, closed by default.
    foreach ($regions as $key => $region) {
      $form[$key] = [
        '#type' => 'details',
        '#title' => $this
          ->t('Region ' . $region['label'] . ' properties'),
      ];

      $form[$key]['display_background_color'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display background color'),
        '#default_value' => $this->configuration[$key]['display_background_color'],
      ];

      $form[$key]['background_color'] = [
        '#type' => 'color',
        '#title' => $this
          ->t('Background color'),
        '#default_value' => $this->configuration[$key]['background_color'],
      ];

      $form[$key]['background_color_opacity'] = [
        '#title' => $this->t('Background color Opacity'),
        '#type' => 'number',
        '#min' => 0,
        '#max' => 1,
        '#step' => 0.01,
        '#required' => TRUE,
        '#default_value' => $this->configuration[$key]['background_color_opacity'],
        '#error_no_message' => TRUE,
      ];

      $form[$key]['display_background_image'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Display background image'),
        '#default_value' => $this->configuration[$key]['display_background_image'],
      ];

      $form[$key]['background_image'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Background Image'),
        '#upload_location' => 'public://layout_builder_awesome_sections/' . date("Y-m-d"),
        '#multiple' => FALSE,
        '#default_value' => $this->configuration[$key]['background_image'],
        '#upload_validators' => [
          'file_validate_extensions' => ['gif png jpg jpeg svg'],
          'file_validate_size' => '30M',
        ],
      ];

      $form[$key]['padding_left'] = [
        '#type' => 'number',
        '#title' => $this
          ->t('Padding left (px)'),
        '#min' => 0,
        '#max' => 100,
        'step' => 1,
        '#default_value' => $this->configuration[$key]['padding_left'],
      ];
      $form[$key]['padding_right'] = [
        '#type' => 'number',
        '#title' => $this
          ->t('Padding right (px)'),
        '#min' => 0,
        '#max' => 100,
        'step' => 1,
        '#default_value' => $this->configuration[$key]['padding_right'],
      ];
      $form[$key]['padding_top'] = [
        '#type' => 'number',
        '#title' => $this
          ->t('Padding Top (px)'),
        '#min' => 0,
        '#max' => 100,
        'step' => 1,
        '#default_value' => $this->configuration[$key]['padding_top'],
      ];
      $form[$key]['padding_bottom'] = [
        '#type' => 'number',
        '#title' => $this
          ->t('Padding bottom (px)'),
        '#min' => 0,
        '#max' => 100,
        'step' => 1,
        '#default_value' => $this->configuration[$key]['padding_bottom'],
      ];

      $form[$key]['min_height'] = [
        '#type' => 'number',
        '#title' => $this
          ->t('Min height (px)'),
        '#min' => 0,
        '#max' => 2000,
        'step' => 1,
        '#default_value' => $this->configuration[$key]['min_height'],
      ];

      $form[$key]['vertical_align'] = [
        '#type' => 'select',
        '#title' => $this->t('Vertical Align'),
        '#options' => [
          'none' => $this->t('-none-'),
          'vertical-align-start' => $this->t('Start'),
          'vertical-align-center' => $this->t('Center'),
          'vertical-align-end' => $this->t('End'),
        ],
        '#default_value' => $this->configuration[$key]['vertical_align'],
      ];

      $form[$key]['region_class'] = [
        '#type' => 'textfield',
        '#title' => $this
          ->t('Class'),
        '#default_value' => $this->configuration[$key]['region_class'],
        '#size' => 60,
        '#maxlength' => 128,
      ];

      $form[$key]['style'] = [
        '#type' => 'textfield',
        '#title' => 'Style',
        '#description' => $this->t('Inline CSS styles. <em>In general, inline CSS styles should be avoided.</em>'),
        '#default_value' => $this->configuration[$key]['style'],
      ];

      $form[$key]['mobile_style'] = [
        '#type' => 'textfield',
        '#title' => 'Mobile style',
        '#description' => $this->t('Inline CSS styles. <em>In general, inline CSS styles should be avoided.(Add !important if it override the same pc style)</em>'),
        '#default_value' => $this->configuration[$key]['mobile_style'],
      ];
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    /** @var \Drupal\Core\Layout\LayoutDefinition $plugin_definition */
    $plugin_definition = $this->getPluginDefinition();
    $regions = $plugin_definition->getRegions();
    if (!isset($this->configuration['uuid'])) {
      $this->configuration['uuid'] = \Drupal::service('uuid')->generate();
    }
    $this->configuration['section_properties'] = $values['section_properties'];
    $this->configuration['section_class'] = $values['section_class'];

    foreach ($regions as $key => $region) {
      $this->configuration[$key] = $values[$key];
      $this->configuration[$key]['background_color_rgba'] = $this->hexToRgba($values[$key]['background_color'], $values[$key]['background_color_opacity']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    $plugin_definition = $this->getPluginDefinition();
    // Notice $definition_regions is different with $regions.
    $definition_regions = $plugin_definition->getRegions();
    $region_count = count($definition_regions);
    $build['#attributes']['class'] = [
      'layout',
      $this->getPluginDefinition()->getTemplate(),
      'uuid-' . $this->configuration['uuid'],
      $this->configuration['section_properties']['section_class'],
    ];
    //    $build['#attributes']['style'] = ["display:flex;"];
    if ($region_count > 1) {
      $build['#attributes']['style'] = ["display:flex;"];

      $mobile_breakpoint = $this->configuration['section_properties']['mobile_breakpoint'] ?? NULL;
      if (!empty($mobile_breakpoint)) {
        $build['#attributes']['class'][] = 'mobile-breakpoint-' . $mobile_breakpoint;
      }
    }

    if (!empty($this->configuration['section_properties']['background_image'])) {
      $imageFileId = implode($this->configuration['section_properties']['background_image']);
      /** @var \Drupal\file\Entity\File $image */
      $image = File::load($imageFileId);
      if ($image != NULL) {
        $image->setPermanent();
        $image->save();
        $build['#settings']['background_image_style'] = 'background-image:url(' . $image->createFileUrl() . ');background-repeat: no-repeat;background-size: cover;background-position: center;';
//        $build['#attributes']['style'][] = 'background-image:url(' . $image->createFileUrl() . ');';
//        $build['#attributes']['style'][] = 'background-repeat: no-repeat;';
//        $build['#attributes']['style'][] = 'background-size: cover;';
//        $build['#attributes']['style'][] = 'background-position: center;';
      }
    }

    if ($region_count == 1) {
      foreach ($definition_regions as $key => $region) {
        $build[$key]['#attributes']['style'][] = 'width: 100%;';
      }
    }
    if (isset($this->configuration['section_properties']['style'])) {
      $build['#attributes']['style'][] = $this->configuration['section_properties']['style'];
    }

    if ($region_count > 1) {
      $region_keys = array_keys($definition_regions);
      for ($i = 1; $i < $region_count - 1; $i++) {
        $build[$region_keys[$i]]['#attributes']['class'][] = "awesome-gutter";
      }
      $build[$region_keys[0]]['#attributes']['class'][] = "awesome-gutter-right";
      $build[$region_keys[$region_count - 1]]['#attributes']['class'][] = "awesome-gutter-left";
    }

    foreach ($definition_regions as $key => $region) {

      $build[$key]['#attributes']['style'][] = 'position: relative;';
      // $build[$key]['#attributes']['style'][] = 'z-index:0;';
      if (isset($this->configuration[$key]['region_class'])) {
        $build[$key]['#attributes']['class'][] = $this->configuration[$key]['region_class'];
      }
      if (!empty($this->configuration[$key]['display_background_image'])) {
        if (!empty($this->configuration[$key]['background_image'])) {
          $imageFileId = implode($this->configuration[$key]['background_image']);
          /** @var \Drupal\file\Entity\File $image */
          $image = File::load($imageFileId);
          if ($image != NULL) {
            $image->setPermanent();
            $image->save();
            $build[$key]['#attributes']['style'][] = 'background-image:url(' . $image->createFileUrl() . ');';
            $build[$key]['#attributes']['style'][] = 'background-repeat: no-repeat;';
            $build[$key]['#attributes']['style'][] = 'background-size: cover;';
            $build[$key]['#attributes']['style'][] = 'background-position: center;';
          }
        }
      }

      if (isset($this->configuration['section_properties']['column_width'][$key])) {
        $build[$key]['#attributes']['style'][] = "width:" . $this->configuration['section_properties']['column_width'][$key] . '%;';
      }
      if (isset($this->configuration[$key]['padding_left'])) {
        $build[$key]['#attributes']['style'][] = "padding-left:" . $this->configuration[$key]['padding_left'] . "px;";
      }
      if (isset($this->configuration[$key]['padding_top'])) {
        $build[$key]['#attributes']['style'][] = "padding-top:" . $this->configuration[$key]['padding_top'] . "px;";
      }
      if (isset($this->configuration[$key]['padding_right'])) {
        $build[$key]['#attributes']['style'][] = "padding-right:" . $this->configuration[$key]['padding_right'] . "px;";
      }
      if (isset($this->configuration[$key]['padding_bottom'])) {
        $build[$key]['#attributes']['style'][] = "padding-bottom:" . $this->configuration[$key]['padding_bottom'] . "px;";
      }
      if (isset($this->configuration[$key]['style'])) {
        $build[$key]['#attributes']['style'][] = $this->configuration[$key]['style'];
      }
      if (isset($this->configuration[$key]['min_height'])) {
        $build[$key]['#attributes']['style'][] = "min-height:" . $this->configuration[$key]['min_height'] . "px;";
      }
      if (isset($this->configuration[$key]['vertical_align']) && $this->configuration[$key]['vertical_align'] != 'none') {
        $build[$key]['#attributes']['class'][] = $this->configuration[$key]['vertical_align'];
      }
    }
    return $build;
  }

  public function hexToRgba($hex, $opacity) {
    /** @var \Mexitek\PHPColors\Color $color */
    $color = new Color($hex);
    $color_rgb_array = $color->getRgb();
    return 'rgba(' . $color_rgb_array['R'] . ',' . $color_rgb_array['G'] . ',' . $color_rgb_array['B'] . ',' . $opacity . ')';
  }

}
