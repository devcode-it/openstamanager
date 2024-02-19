/**
 * Capture dialog using a STU device.
 **/
class StuCaptDialog {
	
    constructor(config) {
		if (config) {
		    this.config = config;		
				
		    if (this.config.encryption) {
		        if (!this.config.encryption.sessionID) {
					this.config.encryption.sessionID = 0xc0ffee;
				}
		    }
		
		    if (this.config.sizeMode == undefined) {
			    this.config.sizeMode = "stu";
		    }
			
			
			this.config.strokeColor = config.strokeColor ?? "#0202FE";
			this.config.strokeSize = config.strokeSize ?? 6;
			this.config.showWait = config.showWait ?? true;
			this.config.stuDevice = config.stuDevice;
			
		} else {
			this.config = {showWait:true, sizeMode:"stu", strokeColor:"#0202FE", strokeSize:6 };
		}
	    
		this.mPenData = Array();
		this.onClearListeners = new Array();
	    this.onCancelListeners = new Array();
	    this.onOkListeners = new Array();
	}
	 
	
    /**
     * Connect to the first STU device found, and open the capture dialog.
	 * @param {string} - Name of the person who is going to sign.
	 * @param {string} - Reason for signing.
	 * @param {IntegrityType} - Hash method to maintain the signature integrity. None by default.
	 * @param {Hash} - Hash of an attached document. None by default.
	 * @param {string} - osInfo, string indicating the OS.
     * @param {string} - nicInfo.
     **/	 
    async open(sigObj, who, why, where, extraData, integrityType, documentHash, osInfo, nicInfo) {	
	    if (!this.config.stuDevice) {
	        let devices = await com.WacomGSS.STU.UsbDevice.requestDevices();
	        if (devices.length > 0) {
		        this.config.stuDevice = devices[0];		
	        } else {
		        throw "No STU devices found";
	        }
	    }
	
	    this.mTablet = new com.WacomGSS.STU.Tablet();		
		this.mTablet.addTabletHandler(this);
		
		if (this.config.encryption) {
			if (this.config.encryption.encryptionHandler) {
			    this.mTablet.setEncryptionHandler(this.config.encryption.encryptionHandler);	
			}
			if (this.config.encryption.encryptionHandler2) {
	            this.mTablet.setEncryptionHandler2(this.config.encryption.encryptionHandler2);
			}
		}
		
        try {		
	        await this.mTablet.usbConnect(this.config.stuDevice);	
		} catch (e) {
			alert("STU Device not found");
		}
	    this.mCapability = await this.mTablet.getCapability();
	    this.mInformation = await this.mTablet.getInformation();
	    this.mInkThreshold = await this.mTablet.getInkThreshold();
	  
	    try {
		    await this.mTablet.setPenDataOptionMode(com.WacomGSS.STU.Protocol.PenDataOptionMode.TimeCountSequence);	
	    } catch (e) {
	    }
				    
		
        let canvasWidth = 0;
		let canvasHeight = 0;
		
		if ((this.config.sizeMode == "fixed") && (this.config.width) && (this.config.height)) {
			// fixed takes the values from the parameters
			canvasWidth = parseInt(this.config.width);
			canvasHeight = parseInt(this.config.height);
		} else if (this.config.attachTo) {
			const parentWidth = $("#"+this.config.attachTo).width();
			const parentHeight = $("#"+this.config.attachTo).height();
			
			const pixelWidth = (96*this.mCapability.tabletMaxX*0.01)/25.4;
	        const pixelHeight = (96*this.mCapability.tabletMaxY*0.01)/25.4;		  
			
		    if (this.config.sizeMode == "fit") {
				canvasWidth = parentWidth;
				canvasHeight = parentHeight;
			} else if (this.config.sizeMode == "strech") {
				if (pixelWidth > pixelHeight) {				
				    canvasWidth = parentWidth;
				    canvasHeight = (pixelHeight/pixelWidth) * canvasWidth;									
			    } else {
				    canvasHeight = parentHeight;
				    canvasWidth = (pixelWidth/pixelHeight)*canvasHeight;
				}
			} else {
				// asume stu dimensions
			    canvasWidth = pixelWidth;
                canvasHeight = pixelHeight;				
			}
        } else {
			// stu mode takes the size from the STU device
			canvasWidth = (96*this.mCapability.tabletMaxX*0.01)/25.4;
	        canvasHeight = (96*this.mCapability.tabletMaxY*0.01)/25.4;		  
		} 		        
			
	    this.mScaleX = canvasWidth / this.mCapability.tabletMaxX;
	    this.mScaleY = canvasHeight / this.mCapability.tabletMaxY;
						
		let useColor = true;				
	    let encodingFlag = com.WacomGSS.STU.Protocol.ProtocolHelper.simulateEncodingFlag(this.mTablet.getProductId(), this.mCapability.ecodingFlag);
	    // Disable color if the bulk driver isn't installed (supportsWrite())
	    if ((encodingFlag & com.WacomGSS.STU.Protocol.EncodingFlag.EncodingFlag_24bit) != 0) {
	        this.mEncodingMode = this.mTablet.supportsWrite() ? com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_24bit_Bulk : com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_24bit; 
	    } else if ((encodingFlag & com.WacomGSS.STU.Protocol.EncodingFlag.EncodingFlag_16bit) != 0) {
	        this.mEncodingMode = this.mTablet.supportsWrite() ? com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_16bit_Bulk : com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_16bit; 
	    } else {
	        // assumes 1bit is available
	        this.mEncodingMode = com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_1bit; 
			useColor = false;
	    }
		
		this.config.width = canvasWidth;
		this.config.height = canvasHeight;
		this.config.title = this.mInformation.modelName;
		//this.config.borderColor = "#cccccc";
		this.config.source = {mouse:false, touch:false, pen:false, stu:true},
		this.sigCaptDialog = new SigCaptDialog(this.config);		
		this.sigCaptDialog.getCaptureData = this.getCaptureData.bind(this);
		this.sigCaptDialog.addEventListener("clear", this.onClearBtn.bind(this));
		this.sigCaptDialog.addEventListener("cancel", this.onCancelBtn.bind(this));
		this.sigCaptDialog.addEventListener("ok", this.onOkBtn.bind(this));
		
		await this.sigCaptDialog.open(sigObj, who, why, where, extraData, integrityType, documentHash, osInfo, "", nicInfo);
						
		//store the background image in order for it to be reused when the screen is cleared
		let canvas = await this.drawImageToCanvas(this.sigCaptDialog.createScreenImage(useColor));
		let ctx = canvas.getContext("2d");						
		this.mDeviceBackgroundImage = com.WacomGSS.STU.Protocol.ProtocolHelper.resizeAndFlatten(canvas, 0, 0, canvasWidth, canvasHeight, 
	                                                                               this.mCapability.screenWidth, this.mCapability.screenHeight, this.mEncodingMode, com.WacomGSS.STU.Protocol.ProtocolHelper.Scale.Stretch, "white", false, false);																							
		
		if (this.config.encryption) {
			if ((this.mTablet.isSupported(com.WacomGSS.STU.Protocol.ReportId.EncryptionStatus)) ||
	           (await com.WacomGSS.STU.Protocol.ProtocolHelper.supportsEncryption(this.mTablet.getProtocol()))) {						   				
			    await this.mTablet.startCapture(this.config.encryption.sessionID);
                this.mIsEncrypted = true;
			}
		}
		
		// put color ink
		if (useColor) {						
		    let htc = await this.mTablet.getHandwritingThicknessColor();
		
            let components = this.hexToRgb(this.config.strokeColor);			
            htc.penColor = this.rgb3216(components.r, components.g, components.b);			
			htc.penThickness = this.config.strokeSize;
			await this.mTablet.setHandwritingThicknessColor(htc);
		}
		
        const reportCountLengths = this.mTablet.getReportCountLengths();		
		if (reportCountLengths[com.WacomGSS.STU.Protocol.ReportId.RenderingMode_$LI$()] !== undefined) {
            await this.mTablet.setRenderingMode(com.WacomGSS.STU.Protocol.RenderingMode.WILL);
        }

	    // Enable the pen data on the screen (if not already)
	    await this.mTablet.setInkingMode(com.WacomGSS.STU.Protocol.InkingMode.On);	  	  
		
		// Initialize the screen
		await this.clearScreen();							
    }
	
	rgb3216(r, g, b) {
		return ((r & 0xf8) << 8) | ((g & 0xfc) << 3) | ((b & 0xf8) >> 3);
	}
	
	hexToRgb(hex) {
        var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }
	
	/**
     * Add an event listener
     * @param {string} eventType - The type of the listener, can be "clear", "cancel" or "ok".
     * @param {function} listener - The function that will handle the event
     **/
    addEventListener(eventType, listener) {
	    switch (eventType) {
		    case "clear"  : this.onClearListeners.push(listener);  break;
		    case "cancel" : this.onCancelListeners.push(listener); break;
		    case "ok"     : this.onOkListeners.push(listener);     break;
	    }
    }
	
	async drawWait() {	
        const canvas = document.createElement("canvas");
		canvas.width = this.mCapability.screenWidth;
		canvas.height = this.mCapability.screenHeight;
		
		const ctx = canvas.getContext("2d");	
	    ctx.font = "60pt Wingdings";	
        const hourglass = String.fromCharCode(0x36);		
		const metrics = ctx.measureText(hourglass);
		const width = metrics.width;
		const height = metrics.fontBoundingBoxAscent + metrics.fontBoundingBoxDescent;
		const size = Math.max(width, height);
		const lineWidth = 3;		        
		
		canvas.width = size;
		canvas.height = size;
		
		ctx.fillStyle = "white";
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		
		ctx.fillStyle = "black";
		ctx.font = "60pt Wingdings";	
		ctx.fillText(hourglass, (width/2)-lineWidth, ((height+metrics.fontBoundingBoxAscent)/2)-lineWidth);
		
		ctx.strokeStyle = "black";
		ctx.lineWidth = lineWidth;
		ctx.beginPath();
		ctx.arc(size/2, size/2, (size-lineWidth*2)/2, 0, 2 * Math.PI);
		ctx.stroke();		
				
		const x = Math.floor(this.mCapability.screenWidth/2 - size/2);
		const y = Math.floor(this.mCapability.screenHeight/2 - size/2);
		const image = com.WacomGSS.STU.Protocol.ProtocolHelper.resizeAndFlatten(canvas, 0, 0, canvas.width, canvas.height, 
	                                                                            canvas.width, canvas.height, this.mEncodingMode, 
		  																	    0, "white", false, false);																																				  		    
		
		const rect = new com.WacomGSS.STU.Protocol.Rectangle(x, y, x+canvas.width-1, y+canvas.height-1);
		await this.mTablet.writeImageArea(this.mEncodingMode, rect, image);				
	}
	
	async clearScreen() {	    
        this.sigCaptDialog.stopCapture();
		await this.mTablet.setClearScreen();
		
		if ((this.config.showWait) &&
		   (this.mTablet.isSupported(com.WacomGSS.STU.Protocol.ReportId.StartImageDataArea_$LI$()))) {
		       await this.drawWait();		
		}
	    await this.mTablet.writeImage(this.mEncodingMode, this.mDeviceBackgroundImage);		
	    this.sigCaptDialog.startCapture();
    }
	
	async disconnect() {	 
        // Ensure that you correctly disconnect from the tablet, otherwise you are 
        // likely to get errors when wanting to connect a second time.
        if (this.mTablet != null) {
	        if (this.mIsEncrypted) {
	            await this.mTablet.endCapture();
		        this.mIsEncrypted = false;
	        }		
		
	        await this.mTablet.setInkingMode(com.WacomGSS.STU.Protocol.InkingMode.Off);
	        await this.mTablet.setClearScreen();
	        await this.mTablet.disconnect();
        }	
    }
	
	tabletToScreen(penData) {
        // Screen means LCD screen of the tablet.
        return {x:penData.x * this.mScaleX, y:penData.y * this.mScaleY};
    }
	
	clear() {
		if (this.sigCaptDialog) {
		    this.sigCaptDialog.clear();
		}
	}
	
	cancel() {
		if (this.sigCaptDialog) {
		    this.sigCaptDialog.cancel();
		}
	}
	
	accept() {
		if (this.sigCaptDialog) {
		    this.sigCaptDialog.accept();
		}
	}
	
	onClearBtn() {
		if (this.mPenData.length > 0) {
		    this.mPenData = new Array();
		    this.clearScreen();
		}
		this.sigCaptDialog.clearTimeOnSurface();
	}

    onCancelBtn() {
		this.disconnect();
	}		
	
	async onOkBtn() {
	    await this.disconnect();
		this.onOkListeners.forEach(listener => listener());
	}
	
	onPenDataOption(penData, time) {	
	    this.onPenData(penData, time);
    }
  
    onPenDataTimeCountSequence(penData, time) {
	    this.onPenData(penData, time);
    }
  
    onPenDataTimeCountSequenceEncrypted(penData, time) {
	    this.onPenDataTimeCountSequence(penData, time);
    }
  
    onPenDataEncryptedOption(penData, time) {
        this.onPenData(penData.penData1, time);
        this.onPenData(penData.penData2, time);	
    }

    onPenDataEncrypted(penData, time) {
        this.onPenData(penData.penData1, time);
        this.onPenData(penData.penData2, time);	
    }

    onPenData(penData, time) {
        //console.log(JSON.stringify(penData));	
        if (!penData.timeCount) {
            penData.timeCount = Math.trunc(time)%1000000;
	    }
		
        const pt = this.tabletToScreen(penData);
		const btnIndex = this.sigCaptDialog.getButton(pt);
		
		const isDown = (penData.pressure > this.mInkThreshold.onPressureMark);
        if (!this.mIsDown) {
            if (isDown) {
                // transition to down we save the button pressed
				this.mBtnIndex = btnIndex;
                if (this.mBtnIndex == -1) {
                    // We have put the pen down outside a button.
                    // Treat it as part of the signature.
		            this.mPenData.push(penData);
		  
		            var downEvent = new PointerEvent("pointerdown", {
			                   pointerId: 1,
                               pointerType: "stu",							   
                               isPrimary: true,
							   clientX: pt.x,
							   clientY: pt.y,
							   pressure: penData.pressure/this.mCapability.tabletMaxPressure,
							   buttons: 1
                             });
							 
					//downEvent.timeStamp = penData.timeCount;
					this.sigCaptDialog.onDown(downEvent);	
                }
            } else {
		        // hover point
		        this.mPenData.push(penData);	
	        }
        } else {
	        if (!isDown) {
                // transition to up
				if (this.mBtnIndex > -1) {
					if (btnIndex == this.mBtnIndex) {
						// The pen is over the same button that was pressed
						this.sigCaptDialog.clickButton(this.mBtnIndex);
					}
				} else {
				    var upEvent = new PointerEvent("pointerup", {
			                   pointerId: 1,
                               pointerType: "stu",							   
                               isPrimary: true,
							   clientX: pt.x,
							   clientY: pt.y,
							   pressure: penData.pressure/this.mCapability.tabletMaxPressure,
							   buttons: 1
                             });
							    
                    //upEvent.timeStamp = penData.timeCount;								
					this.sigCaptDialog.onUp(upEvent);		 							 																		
					this.mPenData.push(penData);                    
				}					
            } else {
				// continue inking
				if (this.mBtnIndex == -1) {
				    var moveEvent = new PointerEvent("pointermove", {
			                   pointerId: 1,
                               pointerType: "stu",							   
                               isPrimary: true,
							   clientX: pt.x,
							   clientY: pt.y,
							   pressure: penData.pressure/this.mCapability.tabletMaxPressure,
							   buttons: 1
                             });
							 
				    //moveEvent.timeStamp = penData.timeCount;
				    this.sigCaptDialog.onMove(moveEvent);		 
				    this.mPenData.push(penData);	
				}
			}
        }
		this.mIsDown = isDown;
    }
	
	/**
	 * Generate the signature from the raw data.
	 **/
    getCaptureData() {
	    //Create Stroke Data
        let strokeVector = new Module.StrokeVector();
        let currentStroke = new Module.PointVector();

        let currentStrokeID = 0;
        let isDown = true;
	    let hasDown = false;

        for (let index = 0; index < this.mPenData.length; index++) {
		    if (this.mPenData[index].sw == 0 && !hasDown) {
				// the signature starts with the first pen down, so the hover
				// points before first down are ingnored.
			    continue;
		    }
		
		    hasDown = true;
		
            if ((isDown && this.mPenData[index].sw == 0) || (!isDown && this.mPenData[index].sw == 1)) {			
                isDown = (this.mPenData[index].sw == 1);
				
		        //Move the current stroke data into the strokes array
                strokeVector.push_back({'points': currentStroke});
                currentStroke.delete();
                currentStroke = new Module.PointVector();
                currentStrokeID++;		
            }		
        
            var point = {
                'x': this.mPenData[index].x,
                'y': this.mPenData[index].y,
                'p': this.mPenData[index].pressure,
                't': this.mPenData[index].timeCount,
			    'azimuth': 0, // STU has no azimuth
				'altitude': 0, // STU has no altitude
			    'twist': 0,	// STU has no twist		
                'is_down': this.mPenData[index].sw,
                'stroke_id': currentStrokeID
            };
		
            currentStroke.push_back(point);			
        }		
	
	    //Create capture area character
        var device = {
            'device_max_X': this.mCapability.tabletMaxX,
            'device_max_Y': this.mCapability.tabletMaxY,
            'device_max_P': this.mCapability.tabletMaxPressure,
            'device_pixels_per_m_x': 100000, 
		    'device_pixels_per_m_y': 100000,
            'device_origin_X': 0,
            'device_origin_Y': 1,
			'device_unit_pixels': false
        }	
	
	    var uid2;
	    try {
            // getUid2 will throw if pad doesn't support Uid2
            uid2 = mTablet.getUid2();
        }
        catch (e) {
        }
	
	    if (!uid2) {
		    uid2 = 0;
	    }

        const digitizerInfo = "STU;'"+this.mInformation.modelName+"';"+this.mInformation.firmwareMajorVersion+"."+((parseInt(this.mInformation.firmwareMinorVersion) >> 4) & 0x0f)+"."+(parseInt(this.mInformation.firmwareMinorVersion) & 0x0f)+";"+uid2;
        const timeResolution = 1000;
	
	    const myPromise = new Promise((resolve, reject) => {
			try {	
                const promise = this.sigCaptDialog.sigObj.generateSignature(this.sigCaptDialog.signatory, this.sigCaptDialog.reason, this.sigCaptDialog.where, this.sigCaptDialog.integrityType, this.sigCaptDialog.documentHash, strokeVector, device, this.sigCaptDialog.osInfo, digitizerInfo, this.sigCaptDialog.nicInfo, timeResolution);
	            promise.then((value) => {
					if (value) {
	                    // put the extra data
		                if (this.extraData) {
		                    for (const data of this.extraData) {
		                        this.sigObj.setExtraData(data.name, data.value);
		                    }
		                }
					}	
                    strokeVector.delete();
                    currentStroke.delete();	
					resolve(value);
				});
				
				promise.catch(error => {
					strokeVector.delete();
                    currentStroke.delete();
				    reject(error);
				});
			} catch (exception) {
				strokeVector.delete();
                currentStroke.delete();
				reject(exception);
			}
		});
		
		return myPromise;
    }
	
	drawImageToCanvas(src){
        return new Promise((resolve, reject) => {
            let img = new Image()
            img.onload = () => {
				let canvas = document.createElement("canvas");	
                canvas.height = img.height;
                canvas.width = img.width;	
				let ctx = canvas.getContext("2d");
				ctx.drawImage(img, 0, 0);
				resolve(canvas);
			}
            img.onerror = reject;
            img.src = src;
        })
    }		
}

