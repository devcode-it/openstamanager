//
// stu_capture.js
//
// Displays a form with 3 buttons on the STU pad and on the browser allowing user to input a signature.
// The final signature is then reproduced on a second window on the PC screen
//
// Copyright (c) 2021 Wacom GmbH. All rights reserved.
//
//

var signatureForm;

function captureFromSTU(sigObj, integrityType, hash, extraData) {   
	if (!signatureForm) {
		signatureForm = new SignatureForm(sigObj, integrityType, hash, extraData);		
	}
	signatureForm.connect();
}

class Point {
  constructor(x, y) {
    this.x = x;
	this.y = y;
  }			    
}      
  
// In order to simulate buttons, we have our own Button class that stores the bounds and event handler.
// Using an array of these makes it easy to add or remove buttons as desired.
//  delegate void ButtonClick();
function Button() {
  this.Bounds; // in Screen coordinates
  this.Text;
  this.Click;
}
  
function Rectangle(x, y, width, height) {
  this.x = x;
  this.y = y;
  this.width = width;
  this.height = height;

  this.Contains = function (pt) {
    if (((pt.x >= this.x) && (pt.x <= (this.x + this.width))) &&
      ((pt.y >= this.y) && (pt.y <= (this.y + this.height)))) {
        return true;
    } else {
      return false;
    }
  }
}
  
class SignatureForm {
	
  constructor(sigObj, integrityType, hash, extraData) {
	this.sigObj = sigObj;
	this.integrityType = integrityType;
	this.hash = hash;
	this.extraData = extraData;
	
    // The mIsDown flag is used like this:
	// 0 = up
    // +ve = down, pressed on button number
    // -1 = down, inking
    // -2 = down, ignoring
    this.mIsDown = 0;
	  
	this.mPenData = new Array(); // Array of data being stored. This can be subsequently used as desired. 
	this.currentDevice = null;

	this.onClick = false;
  }
  
  // Connect to the first device
  async connect() {	
	if (!this.currentDevice) {
	  let devices = await com.WacomGSS.STU.UsbDevice.requestDevices();
	  if (devices.length > 0) {
		this.currentDevice = devices[0];		
	  } else {
		  return;
	  }
	}
	
	this.mTablet = new com.WacomGSS.STU.Tablet();
	this.mTablet.setEncryptionHandler(new MyEncryptionHandler());
	this.mTablet.setEncryptionHandler2(new MyEncryptionHandler2());

	try {
	  await this.mTablet.usbConnect(this.currentDevice);	
	  this.mCapability = await this.mTablet.getCapability();
	  this.mInformation = await this.mTablet.getInformation();
	  this.mInkThreshold = await this.mTablet.getInkThreshold();
	  
	  try {
		await this.mTablet.setPenDataOptionMode(com.WacomGSS.STU.Protocol.PenDataOptionMode.TimeCountSequence);	
	  } catch (e) {
	  }
			
	  this.mTablet.addTabletHandler(this);
			
	  //if (this.mTablet.isSupported(com.WacomGSS.STU.Protocol.ReportId.OperationMode_$LI$())) {
		//this.mSignatureMode = true;
	  //}
	this.mSignatureMode = false;	

      const pixelWidth = (96*this.mCapability.tabletMaxX*0.01)/25.4;
	  const pixelHeight = (96*this.mCapability.tabletMaxY*0.01)/25.4;		  
	  //this.createModalWindow(this.mCapability.screenWidth, this.mCapability.screenHeight);	 
      this.createModalWindow(pixelWidth, pixelHeight);	 
			
	  this.mScaleX = this.canvas.width / this.mCapability.tabletMaxX;
	  this.mScaleY = this.canvas.height / this.mCapability.tabletMaxY;
						
	  this.mBtns = new Array(3);
	  this.mBtns[0] = new Button();
	  this.mBtns[1] = new Button();
	  this.mBtns[2] = new Button();

	  if (this.mSignatureMode) {
		  
	    // LCD is 800x480; Button positions and sizes are fixed
		this.mBtns[0].Bounds = new Rectangle(  0, 431, 265, 48);
		this.mBtns[1].Bounds = new Rectangle(266, 431, 265, 48);
		this.mBtns[2].Bounds = new Rectangle(532, 431, 265, 48);			
	  } else if (this.mInformation.modelName != "STU-300") {
		  
	    // Place the buttons across the bottom of the screen.
		const w2 = this.canvas.width / 3;
		const w3 = this.canvas.width / 3;
		const w1 = this.canvas.width - w2 - w3;
		const y = this.canvas.height * 6 / 7;
		const h = this.canvas.height - y;

        this.mBtns[0].Bounds = new Rectangle(0, y, w1, h);
		this.mBtns[1].Bounds = new Rectangle(w1, y, w2, h);
		this.mBtns[2].Bounds = new Rectangle(w1 + w2, y, w3, h);			
		
	  } else {
	    // The STU-300 is very shallow, so it is better to utilise
		// the buttons to the side of the display instead.

		const x = this.mCapability.screenWidth * 3 / 4;
		const w = this.mCapability.screenWidth - x;

		const h2 = this.mCapability.screenHeight / 3;
		const h3 = this.mCapability.screenHeight / 3;
		const h1 = this.mCapability.screenHeight - h2 - h3;

		this.mBtns[0].Bounds = new Rectangle(x, 0, w, h1);
		this.mBtns[1].Bounds = new Rectangle(x, h1, w, h2);
		this.mBtns[2].Bounds = new Rectangle(x, h1 + h2, w, h3);
	  }

	  this.mBtns[0].Text = "Clear";
	  this.mBtns[0].Click = this.btnClearClick.bind(this);
	  this.mBtns[1].Text = "Cancel";
	  this.mBtns[1].Click = this.btnCancelClick.bind(this);			
	  this.mBtns[2].Text = "OK";
	  this.mBtns[2].Click = this.btnOkClick.bind(this);
						
	  // This application uses the same bitmap for both the screen and client (window).
	  this.ctx.lineWidth = 1;
	  this.ctx.strokeStyle = 'black';
	  this.ctx.font = "30px Arial";
			
	  this.ctx.fillStyle = "white";
	  this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
			
	  let encodingFlag = com.WacomGSS.STU.Protocol.ProtocolHelper.simulateEncodingFlag(this.mTablet.getProductId(), this.mCapability.ecodingFlag);
	  // Disable color if the bulk driver isn't installed (supportsWrite())
	  if ((encodingFlag & com.WacomGSS.STU.Protocol.EncodingFlag.EncodingFlag_24bit) != 0) {
	    this.mEncodingMode = this.mTablet.supportsWrite() ? com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_24bit_Bulk : com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_24bit; 
	  } else if ((encodingFlag & com.WacomGSS.STU.Protocol.EncodingFlag.EncodingFlag_16bit) != 0) {
	    this.mEncodingMode = this.mTablet.supportsWrite() ? com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_16bit_Bulk : com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_16bit; 
	  } else {
	    // assumes 1bit is available
	    this.mEncodingMode = com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_1bit; 
	  }
			
	  if (this.mSignatureMode && !await this.initializeSigMode()) {
	    alert("Exception initializing Signature Mode, reverting to normal operation");
	    this.mSignatureMode = false;
	  }
			
	  if (!this.mSignatureMode) {			
	    let btnsColors = ["white", "white", "white"];
		if((this.mEncodingMode & (com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_16bit_Bulk | com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_24bit_Bulk)) != 0)
		  btnsColors = ["lightgrey", "lightgrey", "lightgrey"];	  
	  
        let newCanvas = this.createScreenImage(btnsColors, "black", null);	  
		
		//store the background image in order to be reuse it when clear the screen
		this.mCanvasBackgroundImage = newCanvas.toDataURL("image/jpeg"); 			            			
		this.mDeviceBackgroundImage = com.WacomGSS.STU.Protocol.ProtocolHelper.resizeAndFlatten(newCanvas, 0, 0, newCanvas.width, newCanvas.height, 
	                                                                               this.mCapability.screenWidth, this.mCapability.screenHeight, this.mEncodingMode,0, "white", false, 0);																				
		
        // If you wish to further optimize image transfer, you can compress the image using 
        // the Zlib algorithm.
        const useZlibCompression = false;

        if (this.mEncodingMode == com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_1bit && useZlibCompression) {
           this.mDeviceBackgroundImage = compress_using_zlib(this.mDeviceBackgroundImage); // insert compression here!
           this.mEncodingMode = com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_1bit_ZLib;
        }
		
		// Initialize the screen
		await this.clearScreen();			
	  }

	  if ((this.mTablet.isSupported(com.WacomGSS.STU.Protocol.ReportId.EncryptionStatus)) ||
	       (await com.WacomGSS.STU.Protocol.ProtocolHelper.supportsEncryption(this.mTablet.getProtocol()))) {						   
		
		await this.mTablet.startCapture(0xc0ffee);
        this.mIsEncrypted = true;
	  }			
													
	  // Enable the pen data on the screen (if not already)
	  await this.mTablet.setInkingMode(com.WacomGSS.STU.Protocol.InkingMode.On);
	  	  
	  this.willCanvas = document.createElement("canvas");
	  this.willCanvas.id = "willCanvas";
	  this.willCanvas.style.position = "absolute";
	  this.willCanvas.style.top = this.canvas.style.top;
	  this.willCanvas.style.left = this.canvas.style.left;
      this.willCanvas.height = this.canvas.height;
      this.willCanvas.width = this.canvas.width;
      this.mFormDiv.appendChild(this.willCanvas);      
	  
	  if (this.willCanvas.addEventListener) {
        this.willCanvas.addEventListener("click", this.onCanvasClick.bind(this), false);
      }
	  
	  await this.initInkController(this.willCanvas);
	  
	} catch (e) {
	  alert(e);
	}
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
	
	this.closeModalWindow();
  }
  
  showLoadingScreen(value) {
	  if (value) {
		  //this.canvas.style.display = "none";
		  this.mLoadingImageDiv.style.display = "block";
	  } else {
		  //this.canvas.style.display = "block";
		  this.mLoadingImageDiv.style.display = "none";
	  }
  }
  
  createModalWindow(width, height) {
    this.mModalBackground = document.createElement('div');
    this.mModalBackground.id = "modal-background";
    this.mModalBackground.className = "active";
    this.mModalBackground.style.width = window.innerWidth;
    this.mModalBackground.style.height = window.innerHeight;
    document.getElementsByTagName('body')[0].appendChild(this.mModalBackground);
	
	let titleBarHeight = 25;
	let margin = 2;
	this.mSignatureWindow = document.createElement('div');
	this.mSignatureWindow.id = "signatureWindow";
	this.mSignatureWindow.style.position = "absolute";
	this.mSignatureWindow.style.backgroundColor = "#0097d4";	
	this.mSignatureWindow.style.top = (window.innerHeight / 2) - (height / 2) + "px";
    this.mSignatureWindow.style.left = (window.innerWidth / 2) - (width / 2) + "px";
    this.mSignatureWindow.style.width = (width + margin + margin) + "px";
    this.mSignatureWindow.style.height = (height+titleBarHeight + margin + margin) + "px";
    document.getElementsByTagName('body')[0].appendChild(this.mSignatureWindow);
	
	this.mTitleBar = document.createElement("div");
	this.mTitleBar.id = "titleBar";
	this.mTitleBar.style.width = "100%";
    this.mTitleBar.style.height = (titleBarHeight-5)+"px";
	this.mTitleBar.innerHTML = this.mInformation.modelName;
	this.mSignatureWindow.appendChild(this.mTitleBar);

    this.mFormDiv = document.createElement('div');
    //this.mFormDiv.id = "signatureWindow";
    //this.mFormDiv.className = "active";
	this.mFormDiv.style.position = "absolute";
	this.mFormDiv.style.margin = "2px 2px 2px 2px";
    this.mFormDiv.style.top = titleBarHeight;//(window.innerHeight / 2) - (height / 2) + "px";
    //this.mFormDiv.style.left = "10px";//(window.innerWidth / 2) - (width / 2) + "px";
    this.mFormDiv.style.width = width + "px";
    this.mFormDiv.style.height = height + "px";
    this.mSignatureWindow.appendChild(this.mFormDiv);
	//document.getElementsByTagName('body')[0].appendChild(this.mFormDiv);	

    this.canvas = document.createElement("canvas");	
    this.canvas.id = "myCanvas";
	this.canvas.style.position = "absolute";
    this.canvas.height = this.mFormDiv.offsetHeight;
    this.canvas.width = this.mFormDiv.offsetWidth;	
	this.ctx = this.canvas.getContext("2d");
    this.mFormDiv.appendChild(this.canvas);
	//this.canvas.style.display = "none";	
	
    //if (this.canvas.addEventListener) {
      //this.canvas.addEventListener("click", this.onCanvasClick.bind(this), false);
    //}	 

    this.mLoadingImageDiv = document.createElement('div');
	this.mLoadingImageDiv.style.position = "absolute";
	this.mLoadingImageDiv.style.backgroundColor="white";
	this.mLoadingImageDiv.style.width = "100%";
	this.mLoadingImageDiv.style.height = "100%";
	this.mLoadingImageDiv.innerHTML = '<div id="loadingDiv"><table><tr><td><img src="../common/stu_capture/loading.gif"></td><td>Loading the image, this could take a few seconds...</td></tr></div>';
	this.mFormDiv.appendChild(this.mLoadingImageDiv);
	
	$("#signatureWindow").draggable({handle:"#titleBar"});
  }
	
  // Initialize Signature Mode (STU-540 only)
  async initializeSigMode() {	  
    // Buttons on bitmaps sent to the tablet must be in the order Cancel / OK / Clear. The tablet will then 
    // reorder button images displayed according to parameters passed to it in OperationMode_Signature
    // This application uses Clear / Cancel / OK
	const btnOrder = [2, 0, 1];
	const btnsUpColors = ["blue", "red", "green"];
	const btnsDownColors = ["darkblue", "darkred", "darkgreen"];

    let canvas = this.createScreenImage(btnsUpColors, "black", btnOrder);	  
	let bitmapData = com.WacomGSS.STU.Protocol.ProtocolHelper.resizeAndFlatten(canvas, 0, 0, canvas.width, canvas.height, 
	                                                                           this.mCapability.screenWidth, this.mCapability.screenHeight, this.mEncodingMode, 
	  	   																       com.WacomGSS.STU.Protocol.ProtocolHelper.Scale.Strech, "white", false, 0);																		
	await this.checkSigModeImage(false, bitmapData);
	  
	canvas = this.createScreenImage(btnsDownColors, "white", btnOrder);	  
	bitmapData = com.WacomGSS.STU.Protocol.ProtocolHelper.resizeAndFlatten(canvas, 0, 0, canvas.width, canvas.height, 
	                                                                       this.mCapability.screenWidth, this.mCapability.screenHeight, this.mEncodingMode, 
																	       com.WacomGSS.STU.Protocol.ProtocolHelper.Scale.Strech, "white", false, 0);																		
	await this.checkSigModeImage(true, bitmapData);
       	  
	let sigMode = new com.WacomGSS.STU.Protocol.OperationMode_Signature(2, btnOrder, 0, 0);
	await this.mTablet.setOperationMode(new com.WacomGSS.STU.Protocol.OperationMode(sigMode));
	  
	canvas = this.createScreenImage(btnsUpColors, "black", null);	  	
	this.mCanvasBackgroundImage = canvas.toDataURL("image/jpeg"); 			            			
	
	this.clearScreen();
	return true;
  }
  
  
  createScreenImage(btnColors, txtColor, btnOrder) {	  
    let canvas = document.createElement("canvas");
	canvas.width = this.canvas.width;
	canvas.height = this.canvas.height;
	
	let ctx = canvas.getContext("2d");
	ctx.lineWidth = 1;
	ctx.strokeStyle = 'black';
	ctx.font = "30px Arial";
			
	ctx.fillStyle = "white";
	ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
	  
	// Draw the buttons
	for (let i = 0; i < this.mBtns.length; ++i) {		  
	  // Button objects are created in the order, left-to-right, Clear / Cancel / OK
      // If reordering for Signature Mode (btnOrder != null), use bounds of another button when drawing
      // for image to be sent to tablet.
	  let btn = this.mBtns[i];
	  let bounds = btnOrder != null ? this.mBtns[btnOrder[i]].Bounds : this.mBtns[i].Bounds;
		  
	  if (this.mEncodingMode != com.WacomGSS.STU.Protocol.EncodingMode.EncodingMode_1bit) {
	    ctx.fillStyle = btnColors[i];
		ctx.fillRect(bounds.x, bounds.y, bounds.width, bounds.height);
	  }
		  
	  ctx.fillStyle = txtColor;
	  ctx.rect(bounds.x, bounds.y, bounds.width, bounds.height);
		  
	  let xPos = bounds.x + ((bounds.width / 2) - (ctx.measureText(btn.Text).width / 2));
				
	  let metrics = ctx.measureText(btn.Text);
	  let fontHeight = metrics.fontBoundingBoxAscent + metrics.fontBoundingBoxDescent;
	  let actualHeight = metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent;
				
	  let yOffset = bounds.height - ((bounds.height / 2) - (actualHeight / 2));
	  /*if (m_information.idProduct == enumProductId.STU_300)
	    yOffset = 28;
	  else if (m_information.idProduct == enumProductId.STU_430)
	    yOffset = 26;
	  else
	    yOffset = 40;*/
	  ctx.fillText(btn.Text, xPos, bounds.y + yOffset);
	}	  
		
	ctx.stroke();
				
	  /*if ((this.mTablet.isSupported(com.WacomGSS.STU.Protocol.ReportId.EncryptionStatus)) ||
	    (await com.WacomGSS.STU.Protocol.ProtocolHelper.supportsEncryption(this.mTablet.getProtocol()))) {				
		  ctx.fillStyle = "black";
		  ctx.fillText("\uD83D\uDD12", 20, 50);	
	  }*/
	return canvas;
  }
  
  // Check if a Signature Mode screen image is already stored on the tablet. Download it if not.
  async checkSigModeImage(pushed, imageData) {
    let sigScreenImageNum = 2;
	let romStartImageData = com.WacomGSS.STU.Protocol.RomStartImageData.initializeSignature(this.mEncodingMode, pushed, sigScreenImageNum, [true, true, true]);
	  
	await this.mTablet.setRomImageHash(com.WacomGSS.STU.Protocol.OperationModeType.Signature_$LI$(), pushed, sigScreenImageNum);						
	let romImgHash = await this.mTablet.getRomImageHash();			
	  
	let writeImage = true;				
	if (romImgHash.getResult() == 0) {
	  // There is already an image stored on the tablet corresponding to this image number and pushed state:
	  // compare image hashes to determine if we need to overwrite it.
	  if (arrayEquals(md5.array(imageData), romImgHash.getHash())) {
	    // Image hashes match: no need to write image again
		writeImage = false;
	  }
	}		
    // else - no image on pad, writeImage = true;		
		
	if (writeImage) {
	  // no image on pad
	  await this.mTablet.writeRomImage(romStartImageData, imageData);
	}
  }  
  
  async clearScreen() {	
    if (window.WILL) {
		window.WILL.clear();	
	} else {
		// repaint the background image on the screen.
	    const outer = this;
        const image = new Image();
        image.onload = function () {
	      outer.ctx.drawImage(image, 0, 0);	  
        }
        image.src = this.mCanvasBackgroundImage;
	}
	
    this.showLoadingScreen(true);
	if (!this.mSignatureMode) {
	  // note: There is no need to clear the tablet screen prior to writing an image.
	  await this.mTablet.writeImage(this.mEncodingMode, this.mDeviceBackgroundImage);	
	}
  
    this.mPenData = new Array();
    this.mIsDown = 0;   	
	
	this.showLoadingScreen(false);
  }
  
  closeModalWindow() {	
	this.deleteInkCanvas();
    document.getElementsByTagName('body')[0].removeChild(this.mSignatureWindow);
	const modalBackground = document.getElementById("modal-background");
	  if (modalBackground) {
              document.getElementsByTagName('body')[0].removeChild(modalBackground);	
		  }

    	
  }   
	
  tabletToScreen(penData) {
    // Screen means LCD screen of the tablet.
    return new Point(penData.x * this.mScaleX, penData.y * this.mScaleY);
  }
    
  async onSignatureEvent(keyValue) {
	switch (keyValue) {
	  case 0:
	    await this.btnCancelClick()
        break;
      case 1:
		await this.btnOkClick();
        break;
      case 2:
        await this.btnClearClick();
        break;
    }
  }
  
  onCanvasClick(event) { 
    // Enable the mouse to click on the simulated buttons that we have displayed.

    // Note that this can add some tricky logic into processing pen data
    // if the pen was down at the time of this click, especially if the pen was logically
    // also 'pressing' a button! This demo however ignores any that.

    const posX = event.pageX - $("#willCanvas").offset().left;
    const posY = event.pageY - $("#willCanvas").offset().top;

    for (let i = 0; i < this.mBtns.length; i++) {
      if (this.mBtns[i].Bounds.Contains(new Point(posX, posY))) {
        this.mBtns[i].Click();
        break;
      }
    }
  }
  
  async btnOkClick() {
	  if (this.mPenData.length > 0) {
    await this.getCaptureData();
    await this.btnCancelClick();
    this.renderSignature = true;
	  }
    this.onClick = false;
	  
  }
  
  async btnClearClick() {
    if (this.mPenData.length > 0) {
	  await this.clearScreen();
	}
	  this.onClick = false;
  }
  
  async btnCancelClick() {	  
    await this.disconnect();
	  this.onClick = false;
  }
  
  // Generate the signature image
  async getCaptureData() {
	//Create Stroke Data
    var strokeVector = new Module.StrokeVector();
    var currentStroke = new Module.PointVector();

    var currentStrokeID = 0;
    var isDown = true;
	var hasDown = false;

    for (let index = 0; index < this.mPenData.length; index++) {
		if (this.mPenData[index].sw == 0 && !hasDown) {
			continue;
		}
		
		hasDown = true;
		
        if (isDown && this.mPenData[index].sw == 0 || !isDown && this.mPenData[index].sw == 1) {			
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
			'tilt': 0,
			'twist': 0,			
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
        'device_pixels_per_m_x':  100000,
		'device_pixels_per_m_y':  100000,
        'device_origin_X': 0,
        'device_origin_Y': 1,
		'has_tilt': false,
		'has_twist': false
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

    var digitizerInfo = "STU;'"+this.mInformation.modelName+"';"+this.mInformation.firmwareMajorVersion+"."+((parseInt(this.mInformation.firmwareMinorVersion) >> 4) & 0x0f)+"."+(parseInt(this.mInformation.firmwareMinorVersion) & 0x0f)+";"+uid2;
    var nicInfo = "";
    var timeResolution = 1000;
    var who = "Test user";
    var why = "test signature";
	var where = "";
	
    await this.sigObj.generateSignature(who, why, where, this.integrityType, this.hash, strokeVector, device, digitizerInfo, nicInfo, timeResolution, new Date());
	
	// put the extra data
	for (const [key, value] of this.extraData) {
		this.sigObj.setExtraData(key, value);
    }
	
    //this.hash.delete();
    strokeVector.delete();
    currentStroke.delete();	
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
  
  onPenDataEncryptedOption(penData, time) { // Process incoming pen data
    this.onPenData(penData.penData1, time);
    this.onPenData(penData.penData2, time);	
  }

  onPenDataEncrypted(penData, time) { // Process incoming pen data
    this.onPenData(penData.penData1, time);
    this.onPenData(penData.penData2, time);	
  }

  onPenData(penData, time) { // Process incoming pen data

   // console.log(JSON.stringify(penData));
	  

	  if (this.onClick) {
		  return;
	  }
	
    if (!penData.timeCount) {
      penData.timeCount = Math.trunc(time)%1000000;
	}

    // when the pen goes behind borders there is a bug that onalsy return 0	  
    let pt = this.tabletToScreen(penData);

    let btn = 0; // will be +ve if the pen is over a button.
    for (var i = 0; i < this.mBtns.length; ++i) {
      if (this.mBtns[i].Bounds.Contains(pt)) {
        btn = i + 1;
        break;
      }
    }
  
    if (this.mIsDown == 0)
    {
      const isDown = (penData.pressure > this.mInkThreshold.onPressureMark);

      if (isDown)
      {
        // transition to down
        if (btn > 0) 
        {
          // We have put the pen down on a button.
          // Track the pen without inking on the client.

          this.mIsDown = btn;
        }
        else
        {
          // We have put the pen down somewhere else.
          // Treat it as part of the signature.

          this.mIsDown = -1;
		  this.mPenData.push(penData);
		  this.mPainting = true;
		  
		  var downEvent = new PointerEvent("pointerdown", {
			                   pointerId: 1,
                               bubbles: true,
                               cancelable: true,
                               pointerType: "pen",
							   pressure: penData.pressure/this.mCapability.tabletMaxPressure,
                               isPrimary: true,
							   clientX: pt.x,
							   clientY: pt.y,
							   time: penData.timeCount
                             });
							 
		  window.WILL.begin(InkBuilder.createPoint(downEvent)); 					 
        }
      } else {
		  // hover point
		  this.mPenData.push(penData);	
	  }
    }
    else
    {
	 const isDown = !(penData.pressure <= this.mInkThreshold.offPressureMark);	  
	  if (!isDown)
      {
        // transition to up
        if (btn > 0) {
          // The pen is over a button

          if (btn == this.mIsDown) {
            // The pen was pressed down over the same button as is was lifted now. 
            // Consider that as a clicki!
           this.onClick = true;		  // 
            this.mBtns[btn-1].Click();
          }
        }
	this.mIsDown = 0;      

        if (this.mPainting) {
		this.mPainting = false;
		  this.mPenData.push(penData);
		  
                  if ((penData.x == 0) && (penData.y == 0) && (penData.pressure == 0)) {
			  penData.x = this.lastPenData.x;
			  penData.y = this.lastPenData.y;
			  pt = this.tabletToScreen(penData);
		  }

		  var upEvent = new PointerEvent("pointerup", {
			                   pointerId: 1,
                               bubbles: true,
                               cancelable: true,
                               pointerType: "pen",
							   pressure: penData.pressure/this.mCapability.tabletMaxPressure,
                               isPrimary: true,
							   clientX: pt.x,
							   clientY: pt.y,
							   time: penData.timeCount
                             });
							 
		  window.WILL.end(InkBuilder.createPoint(upEvent));
		}
      } else {
	      if (this.mPainting) {		      
        this.mPenData.push(penData);
		
		var moveEvent = new PointerEvent("pointermove", {
			                   pointerId: 1,
                               bubbles: true,
                               cancelable: true,
                               pointerType: "pen",
                               width: 10,
                               height: 10,
							   pressure: penData.pressure/this.mCapability.tabletMaxPressure,
                               isPrimary: true,
							   clientX: pt.x,
							   clientY: pt.y,
							   time: penData.timeCount
                             });
							 
		  window.WILL.move(InkBuilder.createPoint(moveEvent));
	      }
	     
      }
    }
    this.lastPenData = penData;	  
  }
  
  onEventDataSignature(eventData) {
      this.onSignatureEvent(eventData.getKeyValue());
  }
   
  onEventDataSignatureEncrypted(eventData) {   
      this.onSignatureEvent(eventData.getKeyValue());
  } 

  // Capture any report exception.
  onGetReportException(exception) {
    try {
      exception.getException();
    } catch (e) {
      alert(e);
    }
  }
  
  async initInkController(canvas) {
	const inkColor = "#0000ff";
	let inkCanvas = await new InkCanvasRaster(canvas, canvas.width, canvas.height);
	await BrushPalette.configure(inkCanvas.canvas.ctx);

	window.WILL = inkCanvas;
	WILL.setColor(Color.fromHex(inkColor));
	WILL.type = "raster";
	await WILL.setTool("pen");
  }
  
  async deleteInkCanvas() {
	  await BrushPalette.delete();
	  await window.WILL.delete();
	  window.WILL = null;	

      if (this.renderSignature) {
          this.renderSignature = false;
		  renderSignature(true);
	  }		  
  }    

	onDisconnect(device) {
		if (device == this.currentDevice) {
			if (document.getElementById("modal-background")) {
				this.closeModalWindow();
			}
			alert(device.productName+" has been disconnected, please connect it again.");
			this.currentDevice = null;
			signatureForm = null;
		}
	}
}
