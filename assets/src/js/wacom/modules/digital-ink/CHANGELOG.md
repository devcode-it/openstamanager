# digital-ink ChangeLog

## 1.5.0 (3.0.7)

_2022-07-01_

### Updates
- _BrushApplier_ optimizations - simplify transform - mat4 to mat2d
- _RIFFEncoder_ and _RIFFDecoder_ LIST chunk support added
- _InkBuilderSettings_ extended - keepAllData, keepSplineParameters options added
- _InkPathProducer_ build method optional settings added, additional pipeline data could be achieved from the worker
- _InkModel_ clear method implemented
_ _SplineInterpolator_ is capable to process and SplineFragments as input
- _InkBuilderAsync_ open / close methods added, open workers is a prerequisite for proper usage

### Breaking Changes
- Long dependency update - ECMA 6 compatible version integrated
- Manipulation module refactoring based on C# implementation
- SpatialContext createInstance method is removed, clone method is provided
- InkModel createCopyInstance method is removed, clone method is provided
- ArrayPath / SharedPath primitives replaced from Path implementation
- View name validation - for non valid names error is throwing
- Interval type deprecated, SplineFragment replace it
- _Stroke_ subStroke method is removed, because Interval dependency, slice replace it, based on SplineFragment

## 1.4.2 (3.0.6)

_2021-11-01_

### Updates
- _ConvexHullChainProducerAsync_ worker _ConvexHullProvider_ as external resource
- _Selector_ selection algorythm improvments
- _InkModel_ version support added, reffers UIM version
- _SplineParameter_ implementation and integration

## 1.4.1 (3.0.5)

_2021-10-01_

### Updates
- _View_ tree property is deprecated, use root instead
- _Stroke_ Stroke.onPipeline abstract static could be implemented to provide custom settings for pipeline
- _Stroke_ target property is deprected, Target enum is obsolete, onPipeline should be implemented when specific behaiour is needed.
- Pipeline refactoring, prediction control increased, POLYGON_SIMPLIFIER stage is deprecated, lastPipelineStage defaults to POLYGON_MERGER
- _InkBuilderSettings_ concatSegments option added, mergePrediction is deprecated
- ArrayPath / SharedPath primitives implemented
- Polygon primitive implemented - shape and holes paths, vertices property provides access to triangulated geometry
- InkPath2D refactored - list of polygons, vertices property provides access to triangulated geometry of all underlying polygons, representing the shape
- Pipeline output update for some of stages - Polygon / InkPath2D

### Bugfixes
- _Scalar_ refactoring, DIP fixed (pixel representation), DP and DPI included
- _SensorChannel_ default resolution fix
- _OffscreenCanvasGL_ height property fix

## 1.4.0 (3.0.4)

_2021-09-01_

### Breaking Changes
- _StrokeRendererGL_ streamUpdatedArea is replaced with blendStroke2D for better integration between GL and 2D contexts
- _InkCodec_ decodeInkModel is async

### Updates
- _InputListener_ resize reason listeners added - for orientation, window resolution, screen resolution, InkController resize method argument reason provided, ResizeReason enum provided
- _InputListener_ suppressKeys properly introduced, it configures ctrlKey, altKey, shiftKey and metaKey and by default if any of them is pressed ink is suppressed
- _InputListener_ provides prediction to _InkController_ move method when is browser supported
- _OffscreenLayer2D_ draws with alpha
- _InkBuilderSettings_ excludedPipelineStages, lastPipelineStage option added
- Stroke renderMode defaults to SOURCE_OVER, required
- StrokeRenderers can batch strokes list, blendStrokes implemented
- Brush2D shape frame based on 1 diameter provides thiner strokes generation
- Selector functionallity bug-fixes
- SpatialContext createInstance method provided - could be based on another context (RTree clone)
- Ink data format update to v.3.1.0
- InkOperation protocol is created
- PrecisionDetection compression serialisation functionality introduced, InkCodec precisionCalculator optional property controlls it
- TripleStore refactoring, extends Array

## 1.3.0 (3.0.3)

_2021-01-15_

### Breaking Changes
- _InkBuilder_ configure method should be called only once before starting new path building
- InkBuilderSettings onBuildComplete property is deprecated, InkBuilder onComplete property should be used instead

### Updates
- _InputListener_ - affects ink input when surface transform is available
- _Stroke_ style support implemented
- _Color_ - hex property provides color as hex
- _Matrix_
  - properties setters impl
  - is2D property impl
  - matrix3d support added
  - fromPoints static method impl
- distribution file name updated
- PipelineStage enum available
- InkBuilderSettings updated, pipeline options added:
  - lastPipelineStep - controls where pipeline to complete
  - excludedPipelineStages - excludes stages from pipeline

### Bugfixes
- _InputDevice_ - fix senosor input validation when pointer is not available
- loading BrushGL assets in Safari

## 1.2.0 (3.0.2)

_2020-09-01_

### Breaking Changes

- _InkController_ - interface is updated, registerTouches(changedTouches) method is replaced with registerInputProvider(pointerID, isPrimary), getInkBuilder(changedTouches) with getInkBuilder(pointerID), implementation update is required
- _InkBuilderAbstract_ - property touchID is renamed to pointerID

### Updates

- _InputListener_ - based on PointerEvent only, fallback class is provided when PointerEvent is not available which implementation is based on MouseEvent and TouchEvent
- _SensorPoint_ - PointerEvent based InputListener extends SensorPoint with coalesced and predicted points, when available
- _Matrix_ - provides access to additional properties like scaleX, translateX, etc.
- _InkPath2D_ - 2D ink path type implemented and integrated
- Layer and StrokeRender implementations extended with setTransform method

### Bugfixes

- _PathPoint_ - transform properly applied rotation when available

## 1.1.0 (3.0.1)

_2020-08-11_

### Updates

- _Spline_ - transfrom functionallity provided
- _Stroke_ - transform functionality updated - transform underlying spline and path if available
- _Intersector_ - intersect2 method renamed to intersectSegmentation, doc update
- _InkContext_ - drawSprite is replaced from drawSpritesBatch (batching points sprites)
- _InputDevice_ - decouple sensor point building from ink builder, InputDevice handles building, validation and provides sensor layout as string array
- _InkCanvas2D_ - context attributes support (Layers inherits canvas attributes)

### Bugfixes

- _InkModel_ - provide proper strokes order after manipulations
- _StrokeDrawContext_ - randomSeed fix (int number instead long)
- _StrokeRendererGL_ - renderer configuration color update, overrides path color
- _InkGLContext_ - blend mode MIN fixed, blendMode as property instead method
- _Matrix_ - fromMatrix method do not recreates Matrix when data is Matrix instance
- _PathPointContext_ - improved attributes values analyse, added suppot for tocuh devices with radius range (0, 1)
- _CurvatureBasedInterpolator_ - rotation bug-fix, ts and tf aplyed

## 1.0.0 (3.0.0)

_2020-07-01_

- First release
