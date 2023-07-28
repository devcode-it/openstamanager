const URIBuilder = {
	// host: location.host.split(":").first.split(".").first,
	host: "signature-sdk-for-javascript-sigCaptDialog",

	// type - raster | vector
	getBrushURI(type, name) {
		return `will://${this.host}/${type}-brush/${name}`;
	},

	// type - shape | fill
	getBrushImageURI(type, name) {
		return `will://${this.host}/raster-brush-${type}/${name}`;
	},

	getBrushPrototypeURI(name, query = "") {
		return `will://${this.host}/vector-brush-shape/${name}${query ? "?" : ""}${query}`;
	},

	// type - remap | resolve
	getActionURI(type, name, query = "") {
		return `will://${this.host}/action-${type}/${name}${query ? "?" : ""}${query}`;
	}
};


const BrushPalette = {
	/* **************** VECTOR BRUSH configuration **************** */
	circle: new window.DigitalInk.Brush2D(URIBuilder.getBrushURI("vector", "Circle"), [
		window.DigitalInk.BrushPrototype.create(window.DigitalInk.BrushPrototype.Type.CIRCLE, 0, 4),
		window.DigitalInk.BrushPrototype.create(window.DigitalInk.BrushPrototype.Type.CIRCLE, 2, 8),
		window.DigitalInk.BrushPrototype.create(window.DigitalInk.BrushPrototype.Type.CIRCLE, 6, 16),
		window.DigitalInk.BrushPrototype.create(window.DigitalInk.BrushPrototype.Type.CIRCLE, 18, 32)
	]),

	basic: new window.DigitalInk.Brush2D(URIBuilder.getBrushURI("vector", "Basic"), window.DigitalInk.ShapeFactory.createCircle(3), 0.3),

	/* **************** RASTER BRUSH configuration **************** */
	pencil: new window.DigitalInk.BrushGL(URIBuilder.getBrushURI("raster", "Pencil"), "../common/will/textures/essential_shape.png", "../common/will/textures/essential_fill_11.png", {spacing: 0.15, scattering: 0.15}),

	waterBrush: new window.DigitalInk.BrushGL(URIBuilder.getBrushURI("raster", "WaterBrush"), "../common/will/textures/essential_shape.png", "../common/will/textures/essential_fill_14.png", {
		spacing: 0.1,
		scattering: 0.03,
		blendMode: window.DigitalInk.BlendMode.MAX
	}),

	inkBrush: new window.DigitalInk.BrushGL(URIBuilder.getBrushURI("raster", "InkBrush"),
		[
			"../common/will/textures/fountain_brush_128x128.png",
			"../common/will/textures/fountain_brush_64x64.png",
			"../common/will/textures/fountain_brush_32x32.png",
			"../common/will/textures/fountain_brush_16x16.png",
			"../common/will/textures/fountain_brush_8x8.png",
		],
		"../common/will/textures/essential_fill_8.png",
		{spacing: 0.035, rotationMode: window.DigitalInk.BrushGL.RotationMode.NONE}
	),

	rainbowBrush: new window.DigitalInk.BrushGL(URIBuilder.getBrushURI("raster", "RainbowBrush"), "../common/will/textures/essential_shape.png", "../common/will/textures/essential_fill_8.png", {spacing: 0.15, rotationMode: window.DigitalInk.BrushGL.RotationMode.NONE}),
	crayon: new window.DigitalInk.BrushGL(URIBuilder.getBrushURI("raster", "Crayon"), "../common/will/textures/essential_shape.png", "../common/will/textures/essential_fill_17.png", {spacing: 0.15, scattering: 0.05}),
};

class ValueTransformer {
	static power(v, p, reverse) {
		if (reverse) v = this.reverse(v);
		return v ** p
	}

	static periodic(v, p, reverse) {
		if (reverse) v = this.reverse(v);
		return 0.5 - 0.5 * Math.cos(p * Math.PI * v);
	}

	static sigmoid(v, p, reverse, minValue = 0, maxValue = 1) {
		if (reverse) v = this.reverse(v);

		let sigmoid = (t, k) => (1 + k) * t / (Math.abs(t) + k);

		let middle = (maxValue + minValue) * 0.5;
		let halfInterval = (maxValue - minValue) * 0.5;
		let t = (v - middle) / halfInterval;
		let s = sigmoid(t, p);

		return middle + s * halfInterval;
	}

	static reverse(v) {
		return 1 - v;
	}
}

const tools = {
    /* ******* VECTOR TOOLS ******* */
	basic: {
	    brush: BrushPalette.basic,

		dynamics: {
			size: {
				value: {
					min: 2,
					max: 6
				},

				velocity: {
					min: 100,
					max: 4000
				},

				pressure: {
					min: 0.2,
					max: 0.8
				}
			}
		},

		statics: {}
	},

	pen: {
		brush: BrushPalette.circle,

		dynamics: {
			size: {
				value: {
					min: 0.5,
					max: 1.6,
					remap: v => ValueTransformer.sigmoid(v, 0.62)
				},

				velocity: {
					min: 5,
					max: 210
				}
			},

			rotation: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ROTATION, window.DigitalInk.SensorChannel.Type.AZIMUTH]
			},

			scaleX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_X, window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 1,
					max: 3
				}
			},

			scaleY: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_Y],

				value: {
					min: 1,
					max: 3
				}
			},

			offsetX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 2,
					max: 5
				}
			}
		}
	},

	felt: {
		brush: BrushPalette.circle,

		dynamics: {
			size: {
				value: {
					min: 1.03,
					max: 2.43,
					remap: v => ValueTransformer.periodic(v, 3)
				},

				velocity: {
					min: 33,
					max: 628
				}
			},

			rotation: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ROTATION, window.DigitalInk.SensorChannel.Type.AZIMUTH]
			},

			scaleX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_X, window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 1,
					max: 3
				}
			},

			scaleY: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_Y],

				value: {
					min: 1,
					max: 3
				}
			},

			offsetX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 2,
					max: 5
				}
			}
		}
	},

	brush: {
		brush: BrushPalette.circle,

		dynamics: {
			size: {
				value: {
					min: 3.4,
					max: 17.2,
					remap: v => ValueTransformer.power(v, 1.19)
				},

				velocity: {
					min: 182,
					max: 3547
				}
			},

			rotation: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ROTATION, window.DigitalInk.SensorChannel.Type.AZIMUTH]
			},

			scaleX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_X, window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 1,
					max: 3
				}
			},

			scaleY: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_Y],

				value: {
					min: 1,
					max: 3
				}
			},

			offsetX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 2,
					max: 5
				}
			}
		},

		statics: {
			alpha: 0.7
		}
	},

	marker: {
		brush: BrushPalette.circle,

		statics: {
			size: 3.4,
			alpha: 0.7
		}
	},

	/* ******* RASTER TOOLS ******* */
	pencil: {
		brush: BrushPalette.pencil,

		dynamics: {
			size: {
				value: {
					min: 4,
					max: 5
				},

				velocity: {
					min: 80,
					max: 1400
				}
			},

			alpha: {
				value: {
					min: 0.05,
					max: 0.2
				},

				velocity: {
					min: 80,
					max: 1400
				}
			},

			rotation: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ROTATION, window.DigitalInk.SensorChannel.Type.AZIMUTH]
			},

			scaleX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_X, window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 1,
					max: 3
				}
			},

			scaleY: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_Y],

				value: {
					min: 1,
					max: 3
				}
			},

			offsetX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 2,
					max: 5
				}
			}
		}
	},

	waterBrush: {
		brush: BrushPalette.waterBrush,

		dynamics: {
			size: {
				value: {
					min: 28,
					max: 32,
					remap: v => ValueTransformer.power(v, 3)
				},

				velocity: {
					min: 38,
					max: 1500
				}
			},

			alpha: {
				value: {
					min: 0.02,
					max: 0.25,
					remap: v => ValueTransformer.power(v, 3)
				},

				velocity: {
					min: 38,
					max: 1500
				}
			},

			rotation: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ROTATION, window.DigitalInk.SensorChannel.Type.AZIMUTH]
			},

			scaleX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_X, window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 1,
					max: 3
				}
			},

			scaleY: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_Y],

				value: {
					min: 1,
					max: 3
				}
			},

			offsetX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 2,
					max: 5
				}
			}
		}
	},

	inkBrush: {
		brush: BrushPalette.inkBrush,

		dynamics: {
			size: {
				value: {
					min: 5,
					max: 28
				},

				velocity: {
					min: 50,
					max: 2000
				}
			},

			rotation: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ROTATION, window.DigitalInk.SensorChannel.Type.AZIMUTH]
			},

			scaleX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_X, window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 1,
					max: 3
				}
			},

			scaleY: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_Y],

				value: {
					min: 1,
					max: 3
				}
			},

			offsetX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 2,
					max: 5
				}
			}
		},

		statics: {
			alpha: 1
		}
	},

	rainbowBrush: {
		brush: BrushPalette.rainbowBrush,

		dynamics: {
			size: {
				value: {
					min: 5,
					max: 28
				},

				velocity: {
					min: 50,
					max: 2000
				}
			},

			red: {
				resolve: () => Math.ceil(Math.random() * 255)
			},

			green: {
				resolve: () => Math.ceil(Math.random() * 255)
			},

			blue: {
				resolve: () => Math.ceil(Math.random() * 255)
			},

			alpha: {
				value: {
					min: 0.1,
					max: 0.6
				},

				velocity: {
					min: 50,
					max: 2000
				}
			},

			rotation: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ROTATION, window.DigitalInk.SensorChannel.Type.AZIMUTH]
			},

			scaleX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_X, window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 1,
					max: 3
				}
			},

			scaleY: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_Y],

				value: {
					min: 1,
					max: 3
				}
			},

			offsetX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 2,
					max: 5
				}
			}
		}
	},

	crayon: {
		brush: BrushPalette.crayon,

		dynamics: {
			size: {
				value: {
					min: 18,
					max: 28
				},

				velocity: {
					min: 10,
					max: 1400
				}
			},

			alpha: {
				value: {
					min: 0.1,
					max: 0.6,
					remap: v => ValueTransformer.reverse(v)
				},

				velocity: {
					min: 10,
					max: 1400
				}
			},

			rotation: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ROTATION, window.DigitalInk.SensorChannel.Type.AZIMUTH]
			},

			scaleX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_X, window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 1,
					max: 3
				}
			},

			scaleY: {
				dependencies: [window.DigitalInk.SensorChannel.Type.RADIUS_Y],

				value: {
					min: 1,
					max: 3
				}
			},

			offsetX: {
				dependencies: [window.DigitalInk.SensorChannel.Type.ALTITUDE],

				value: {
					min: 2,
					max: 5
				}
			}
		}
	}
};
