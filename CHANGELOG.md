# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.6.0 - 2016-02-17

### Added

- [#8](https://github.com/zendframework/zend-view/pull/8) adds a new method to
  each of the `Breadcrumbs` and `Menu` navigation helpers, 
  `renderPartialWithParams(array $params = [], $container = null, $partial = null)`.
  This method allows passing parameters to the navigation partial to render,
  just as you would when using the `partial()` view helper.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#15](https://github.com/zendframework/zend-view/pull/15),
  [#17](https://github.com/zendframework/zend-view/pull/17),
  [#35](https://github.com/zendframework/zend-view/pull/35), and
  [#42](https://github.com/zendframework/zend-view/pull/42) update the component
  to be forwards-compatible with the v3 releases of zend-eventmanager,
  zend-servicemanager, and zend-stdlib. The changes include:
  - changes to how events are triggered to ensure they continue working correctly.
  - updates to the plugin manager to be forwards-compatible.
  - updates to helper factories to be forwards-compatible.

## 2.5.3 - 2016-01-19

### Added

- [#5](https://github.com/zendframework/zend-view/pull/5) adds support for the
  `itemprop` attribute in the `headLink()` view helper.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#25](https://github.com/zendframework/zend-view/pull/25) updates
  `PhpRenderer::render()` to no longer lazy-instantiate a `FilterChain`;
  content filtering is now only done if a `FitlerChain` is already
  injected in the renderer.

## 2.5.2 - 2015-06-16

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#4](https://github.com/zendframework/zend-view/pull/4) fixes an issue with
  how the `ServerUrl` detects and emits the port when port-forwarding is in
  effect.

## 2.4.3 - 2015-06-16

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#4](https://github.com/zendframework/zend-view/pull/4) fixes an issue with
  how the `ServerUrl` detects and emits the port when port-forwarding is in
  effect.