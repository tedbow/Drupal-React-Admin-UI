<?php

namespace Drupal\drupal_admin_ui\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * The default controller for all pages taken over by this module.
 */
class DefaultController extends ControllerBase {

  /**
   * The variant manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $variantManager;

  /**
   * The current route.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface
   */
  protected $router;

  /**
   * Constructs a \Drupal\drupal_admin_ui\Controller\DefaultController object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $variant_manager
   *   The variant manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route
   *   The current route match.
   * @param \Drupal\Core\Routing\RouteProviderInterface $router
   *   The route provider.
   */
  public function __construct(PluginManagerInterface $variant_manager, CurrentRouteMatch $route, RouteProviderInterface $router) {
    $this->variantManager = $variant_manager;
    $this->currentRoute = $route;
    $this->router = $router;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.display_variant'),
      $container->get('current_route_match'),
      $container->get('router.route_provider')
    );
  }

  /**
   * Gets the App route for HTML routes.
   *
   * @return array
   *   The render array containing the App route and libraries attached.
   */
  public function getAppRoute() {
    $build = [
      '#attached' => [
        'library' => [
          'drupal_admin_ui/app',
        ],
      ],
      '#markup' => '<div id="root" />',
    ];
    $route = $this->currentRoute->getRouteObject();
    if ($callback_route_name = $route->getOption('_drupal_admin_ui.callback')) {
      $callback_path = Url::fromRoute($callback_route_name)->toString();
      // @todo Handle route parameters.
      $build['#attached']['drupalSettings']['drupal_admin_ui']['callback'] = $callback_path;
    }
    $build['#attached']['drupalSettings']['drupal_admin_ui']['routes'] = $this->getOverriddenRoutes();
    return $build;

  }

  /**
   * Gets the blocks as json.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The Json Response with the blocks.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getBlocks() {
    /** @var \Drupal\drupal_admin_ui\Plugin\DisplayVariant\BlockPageVariant $block_page */
    $block_page = $this->variantManager->createInstance('drupal_admin_ui_page');
    $build = $block_page->build();
    $json['regions'] = $build['#attached']['drupalSettings']['drupal_admin_ui']['regions'];
    return new JsonResponse($json);
  }

  /**
   * Get the routes overridden but this module.
   *
   * @return \Symfony\Component\Routing\RouterInterface[]
   *   The overridden routes.
   */
  protected function getOverriddenRoutes() {
    $routes = [];
    foreach ($this->router->getAllRoutes() as $route_name => $route) {
      if ($route->getOption('_drupal_admin_ui.route')) {
        $routes[$route_name] = $route->getPath();
      }
    }
    return $routes;
  }

}
