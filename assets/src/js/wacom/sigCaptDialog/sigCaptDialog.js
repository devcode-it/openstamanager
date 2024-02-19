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

function getLocateString(string) {
	var deStrings = {"evaluation":"Evaluierung",
	                 "ok":"OK",
					 "cancel":"Abbrechen",
					 "clear":"Löschen",
					 "defaultName":"Kunde",
					 "defaultReason":"Bestätigt"
					};
					 
	var elStrings = {"evaluation":"Αξιολόγηση",
	                 "ok":"Εντάξει",
					 "cancel":"Ακύρωση",
					 "clear":"Διαγραφή",
					 "defaultName":"Πελάτης",
					 "defaultReason":"Επιβεβαιωμένος"
	                };
					
	var enStrings = {"evaluation":"Evaluation",
	                 "ok":"OK",
					 "cancel":"Cancel",
					 "clear":"Clear",
					 "defaultName":"Customer",
					 "defaultReason":"Confirmed"
	                };
					
	var esStrings = {"evaluation":"Evaluación",
	                 "ok":"Aceptar",
					 "cancel":"Cancelar",
					 "clear":"Borrar",
					 "defaultName":"Cliente",
					 "defaultReason":"Confirmado"
	                };	
					
	var frStrings = {"evaluation":"Évaluation",
	                 "ok":"OK",
					 "cancel":"Annuler",
					 "clear":"Effacer",
					 "defaultName":"Client",
					 "defaultReason":"Confirmé"
	                };	
					
	var itStrings = {"evaluation":"Valutazione",
	                 "ok":"OK",
					 "cancel":"Annulla",
					 "clear":"Cancella",
					 "defaultName":"Utente",
					 "defaultReason":"Confermato"
	                };	
					
	var jaStrings = {"evaluation":"評価",
	                 "ok":"OK",
					 "cancel":"キャンセル",
					 "clear":"クリア",
					 "defaultName":"署名者",
					 "defaultReason":"確認済"
					};
					
	var koStrings = {"evaluation":"평가",
	                 "ok":"확인",
					 "cancel":"취소",
					 "clear":"지우기",
					 "defaultName":"고객",
					 "defaultReason":"확인됨"
	                };	
					
	var nlStrings = {"evaluation":"Evaluatie",
	                 "ok":"OK",
					 "cancel":"Annuleren",
					 "clear":"Verwijderen",
					 "defaultName":"Klant",
					 "defaultReason":"Bevestigd"
	                };	
					
	var plStrings = {"evaluation":"Ocena",
	                 "ok":"OK",
					 "cancel":"Anuluj",
					 "clear":"Wyczyść",
					 "defaultName":"Klient",
					 "defaultReason":"Potwierdzono"
	                };	
					
	var ptStrings = {"evaluation":"Avaliação",
	                 "ok":"OK",
					 "cancel":"Cancelar",
					 "clear":"Apagar",
					 "defaultName":"Cliente",
					 "defaultReason":"Confirmado"
	                };	
					
	var ruStrings = {"evaluation":"Оценка",
	                 "ok":"OK",
					 "cancel":"Отмена",
					 "clear":"Удалить",
					 "defaultName":"Клиент",
					 "defaultReason":"Подтверждено"
	                };	
					
	var zhStrings = {"evaluation":"评估",
	                 "ok":"确定",
					 "cancel":"取消",
					 "clear":"清除",
					 "defaultName":"顾客",
					 "defaultReason":"确认"
					};	
					
	var strings = {"de":deStrings,
	               "el":elStrings,
		           "en":enStrings, 
	               "es":esStrings,
				   "fr":frStrings,
				   "it":itStrings,
				   "ja":jaStrings,
				   "ko":koStrings,
				   "nl":nlStrings,
				   "pl":plStrings,
				   "pt":ptStrings,
				   "ru":ruStrings,
				   "zh":zhStrings};
				   
	var userLang = (navigator.language || navigator.userLanguage || "en-GB").split("-")[0]; 
	let strLang = strings[userLang];
	if (strLang) {
	} else {
		strLang = strings["en"];
	}
	return strLang[string];	
}

function getPenOrientation(ev) {    
	let altitudeAngle = 0;
	let azimuthAngle = 0;
	let rotationAngle = 0;
	
	if (ev instanceof PointerEvent) {
	    // Pointer Events for a stylus currently use tiltX, tiltY and twist to give the orientation of the stylus in space. 
	    // However there is an experimental API that include the altitudeAngle and azimuthAngle, so before check if these values exists
	    if (event.altitudeAngle !== undefined && event.azimuthAngle !== undefined) {
		    altitudeAngle = event.altitudeAngle;
		    azimuthAngle = event.azimuthAngle;
	    } else if (event.tiltX !== undefined && event.tilY !== undefined) {
	        let params = tilt2spherical(event.tiltX, event.tiltY);	
		    altitudeAngle = params.altitudeAngle;
		    azimuthAngle = params.azimuthAngle;
	    }
		
		return {"altitude":altitudeAngle, "azimuth":azimuthAngle, "twist":event.twist};	
	} else {
	    const touch = ev.touches ? ev.touches[0] : null;
		
	    if (touch) {
	        if (touch.altitudeAngle) {
		        altitudeAngle = touch.altitudeAngle;
	        }
	
	        if (touch.azimuthAngle) {
		        azimuthAngle = touch.azimuthAngle;
	        }
	
	        if (touch.rotationAngle) {		    
		        rotationAngle = touch.rotationAngle;
		    }
	    }	
	
	    return {"altitude":altitudeAngle, "azimuth":azimuthAngle, "twist":rotationAngle};	  
	}

    function tilt2spherical(tiltX, tiltY){
        const tiltXrad = tiltX * Math.PI/180;
        const tiltYrad = tiltY * Math.PI/180;

        // calculate azimuth angle
        let azimuthAngle = 0;

        if(tiltX == 0){
            if(tiltY > 0){
                azimuthAngle = Math.PI/2;
            } else if(tiltY < 0) {
                azimuthAngle = 3*Math.PI/2;
            }
        } else if(tiltY == 0){
            if(tiltX < 0){
                azimuthAngle = Math.PI;
            }
        } else if(Math.abs(tiltX) == 90 || Math.abs(tiltY) == 90){
            // not enough information to calculate azimuth
            azimuthAngle = 0;
        } else {
            // Non-boundary case: neither tiltX nor tiltY is equal to 0 or +-90
            const tanX = Math.tan(tiltXrad);
            const tanY = Math.tan(tiltYrad);

            azimuthAngle = Math.atan2(tanY, tanX);
            if(azimuthAngle < 0){
                azimuthAngle += 2*Math.PI;
            }
        }

        // calculate altitude angle
        let altitudeAngle = 0;

        if (Math.abs(tiltX) == 90 || Math.abs(tiltY) == 90){
            altitudeAngle = 0
        } else if (tiltX == 0){
            altitudeAngle = Math.PI/2 - Math.abs(tiltYrad);
        } else if(tiltY == 0){
            altitudeAngle = Math.PI/2 - Math.abs(tiltXrad);
        } else {
            // Non-boundary case: neither tiltX nor tiltY is equal to 0 or +-90
            altitudeAngle =  Math.atan(1.0/Math.sqrt(Math.pow(Math.tan(tiltXrad),2) + Math.pow(Math.tan(tiltYrad),2)));
        }

        return {"altitudeAngle":altitudeAngle, "azimuthAngle":azimuthAngle};
    }	
}

class SigCaptDialog {	 

  mapConfig(config) {	    
	  if (config.width) {
		  this.config.width = config.width;
	  }
	  if (config.height) {
		  this.config.height = config.height;
	  }
	  if (config.left != undefined) {
		  this.config.left = config.left;
	  }
	  if (config.top != undefined) {
		  this.config.top = config.top;
	  }
	  if (config.centered != undefined) {
		  this.config.centered = config.centered;
	  }
	  if (config.title) {
		  this.config.title = config.title;
	  }
	  if (config.hasTitle != undefined) {
		  this.config.hasTitle = config.hasTitle;
	  }
	  if (config.borderWidth != undefined) {
		  this.config.borderWidth = config.borderWidth;
	  }
	  if (config.borderColor) {
		  this.config.borderColor = config.borderColor;
	  }
	  if (config.buttonsFont) {
		  this.config.buttonsFont = config.buttonsFont;
	  }
	  if (config.background) {
		  if (config.background.alpha) {
			  this.config.background.alpha = config.background.alpha;
		  }
		  if (config.background.color) {
			  this.config.background.color = config.background.color;
		  }
		  if (config.background.image) {
			  this.config.background.image = config.background.image;
		  }
		  if (config.background.mode) {
			  this.config.background.mode = config.background.mode;
		  }
	  }
	  if (config.reason) {
		  if (config.reason.visible != undefined) {
			  this.config.reason.visible = config.reason.visible;
		  }
		  if (config.reason.fontFace) {
			  this.config.reason.fontFace = config.reason.fontFace;
		  }
		  if (config.reason.fontSize) {
			  this.config.reason.fontSize = config.reason.fontSize;
		  }
		  if (config.reason.color) {
			  this.config.reason.color = config.reason.color;
		  }
		  if (config.reason.offsetY) {
			  this.config.reason.offsetY = config.reason.offsetY;
		  }
		  if (config.reason.offsetX) {
			  this.config.reason.offsetX = config.reason.offsetX;
		  }
	  }
	  if (config.signatory) {
		  if (config.signatory.visible != undefined) {
			  this.config.signatory.visible = config.signatory.visible;
		  }
		  if (config.signatory.fontFace) {
			  this.config.signatory.fontFace = config.signatory.fontFace;
		  }
		  if (config.signatory.fontSize) {
			  this.config.signatory.fontSize = config.signatory.fontSize;
		  }
		  if (config.signatory.color) {
			  this.config.signatory.color = config.signatory.color;
		  }
		  if (config.signatory.offsetY) {
			  this.config.signatory.offsetY = config.signatory.offsetY;
		  }
		  if (config.signatory.offsetX) {
			  this.config.signatory.offsetX = config.signatory.offsetX;
		  }
	  }
	  if (config.signingLine) {
		  if (config.signingLine.visible != undefined) {
			  this.config.signingLine.visible = config.signingLine.visible;
		  }
		  if (config.signingLine.left) {
			  this.config.signingLine.left = config.signingLine.left;
		  }
		  if (config.signingLine.right) {
			  this.config.signingLine.right = config.signingLine.right;
		  }
		  if (config.signingLine.width) {
			  this.config.signingLine.width = config.signingLine.width;
		  }
		  if (config.signingLine.color) {
			  this.config.signingLine.color = config.signingLine.color;
		  }
		  if (config.signingLine.offsetY) {
			  this.config.signingLine.offsetY = config.signingLine.offsetY;
		  }		  		  
	  }
	  if (config.date) {
		  if (config.date.visible != undefined) {
			  this.config.date.visible = config.date.visible;
		  }
		  if (config.date.fontFace) {
			  this.config.date.fontFace = config.date.fontFace;
		  }
		  if (config.date.fontSize) {
			  this.config.date.fontSize = config.date.fontSize;
		  }
		  if (config.date.left) {
			  this.config.date.left = config.date.left;
		  }
		  if (config.date.right) {
			  this.config.date.right = config.date.right;
		  }
		  if (config.date.width) {
			  this.config.date.width = config.date.width;
		  }
		  if (config.date.color) {
			  this.config.date.color = config.date.color;
		  }
		  if (config.date.offsetY) {
			  this.config.date.offsetY = config.date.offsetY;
		  }		  		  
	  }
	  if (config.attachTo) {
	      this.config.attachTo = config.attachTo;
	  }
	  if (config.modal != undefined) {
		  this.config.modal = config.modal;
	  }
	  if (config.draggable != undefined) {
		  this.config.draggable = config.draggable;
	  }
	  if (config.source) {
		  if (config.source.mouse != undefined) {
			  this.config.source.mouse = config.source.mouse;
		  }
		  if (config.source.touch != undefined) {
			  this.config.source.touch = config.source.touch;
		  }
		  if (config.source.pen != undefined) {
			  this.config.source.pen = config.source.pen;
		  }
		  if (config.source.stu != undefined) {
			  this.config.source.stu = config.source.stu;
		  }
	  }

	  if (config.strokeColor != undefined) {
		  this.config.strokeColor = config.strokeColor;
	  }
	  if (config.strokeSize != undefined) {
		  this.config.strokeSize = config.strokeSize;
	  }
	  
	  if (config.buttons) {
		  this.config.buttons = config.buttons;
	  }
	  
	  if (config.timeOut) {
		  this.config.timeOut = config.timeOut;
	  }
	  
	  if (config.minTimeOnSurface) {
		  this.config.minTimeOnSurface = config.minTimeOnSurface;
	  }
	  
	  if (config.onOutSide) {
		  this.config.onOutSide = config.onOutSide;
	  }
	  
	  if (config.allowZeroPressure != undefined) {
		  this.config.allowZeroPressure = config.allowZeroPressure;
	  }
  }
	
  constructor(config) {	      
	this.config = {
	  width: 400,
	  height: 300,
	  left: 0,
	  top:0,
	  centered:true,
	  title: "My Tittle",
	  borderColor: "#0097d4",
	  borderWidth: "1p",
	  hasTitle: true,
	  buttons: [{text: "*clear", textColor: "black", backgroundColor: "lightgrey", borderWidth: 0, borderColor: "black", onClick: this.btnClearClick.bind(this)}, 
	            {text: "*cancel", textColor: "black", backgroundColor: "lightgrey", borderWidth: 0, borderColor: "black", onClick: this.btnCancelClick.bind(this)}, 
				{text: "*ok", textColor: "black", backgroundColor: "lightgrey", borderWidth: 0, borderColor: "black", onClick: this.btnOkClick.bind(this)}],
	  buttonsFont: "Arial",
	  background: {alpha: 1.0, color: "white", mode:"fit"},
	  reason: {visible:true, fontFace:"Arial", fontSize:16, color:"black", offsetY:10, offsetX:5},
	  signatory: {visible:true, fontFace:"Arial", fontSize:16, color:"black", offsetY:5, offsetX:30},
	  date: {visible:true, fontFace:"Arial", fontSize:16, color:"black", offsetY:20, offsetX:30},
	  signingLine: {visible:true, left:30, right:30, width:2, color:"grey", offsetY:5},
	  source: {mouse:true, touch:true, pen:true, stu:true},	 
	  strokeColor:"#0202FE",
	  strokeSize:6,	  
	  modal: true,
	  draggable: true,
	  timeOut: {enabled:false, time:10000, onTimeOut:null},
	  allowZeroPressure: true
    };  
	
	if (config) {
	    this.mapConfig(config);
	}
	
	this.capturedPoints = new Array();
	this.onClearListeners = new Array();
	this.onCancelListeners = new Array();
	this.onOkListeners = new Array();

	this.timeOnSurface = 0;	
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
  
  /**
   * Connect to the first STU device found, and open the capture dialog.
   * @param {string} - Name of the person who is going to sign.
   * @param {string} - Reason for signing.
   * @param {string} - Where, indicating the place where the signature is captured.
   * @param {IntegrityType} - Hash method to maintain the signature integrity. None by default.
   * @param {Hash} - Hash of an attached document. None by default.
   * @param {string} - osInfo, string indicating the OS.
   * @param {string} - digitizerInfo, string indicationg the digitalizer.
   * @param {string} - nicInfo.
  **/	 
  async open(sigObj, who, why, where, extraData, integrityType, documentHash, osInfo, digitizerInfo, nicInfo) {	  
      this.sigObj = sigObj;
	  this.extraData = extraData;
	  
      if (who) {
	      this.signatory = who;
	  } else {
	      this.signatory = getLocateString('defaultName');
	  }
		
	  if (why) {
	      this.reason = why;
	  } else {
	      this.reason = getLocateString('defaultReason');
	  }
	  
	  if (where) {
		  this.where = where;
	  } else {
		  this.where = "";
	  }
		
	  if (integrityType) {
	      this.integrityType = integrityType;
	  } else {
		  this.integrityType = Module.KeyType.None;
	  }
		
	  if (documentHash) {
		  this.documentHash = documentHash;
	  } else {
		  this.documentHash = new Module.Hash(Module.HashType.None);	
	  }
	  
	  if (osInfo) {
		  this.osInfo = osInfo;
	  } else {
		  this.osInfo = window.navigator.userAgent;
	  }
	  
	  if (digitizerInfo) {
		  this.digitizerInfo = digitizerInfo;
	  } else {
		  this.digitizerInfo = "Javascript canvas";
	  }
	  
	  if (nicInfo) {
		  this.nicInfo = nicInfo;
	  } else {
		  this.nicInfo = "";
	  }
    
	  this.createWindow(parseInt(this.config.width), parseInt(this.config.height));
	  	  	  
	  this.drawingCtx = this.drawingCanvas.getContext("2d");
	  this.drawingCtx.fillStyle = this.config.strokeColor;
	  this.drawingCtx.strokeStyle = this.config.strokeColor;
	  this.drawingCtx.lineJoin = "round";	  
	  const devicePixelRatio = window.devicePixelRatio || 1;
	  this.drawingCtx.scale(devicePixelRatio, devicePixelRatio);
	  this.drawingPath = new Path2D();
	  
	  this.mBtns = new Array(this.config.buttons.length);
	  if (this.config.buttons.length > 0) {
		  const y = this.canvas.height * 6 / 7;
	      const h = this.canvas.height - y;
		  const w = this.canvas.width / this.config.buttons.length;
	  	      
	      for (var i=0; i<this.config.buttons.length; i++) {
	          this.mBtns[i] = new Button();  
		  
		      // Place the buttons across the bottom of the screen.  
		      this.mBtns[i].Bounds = new Rectangle((i*w), y, w, h);
			  
			  let buttonText;
			  if (this.config.buttons[i].text.startsWith("*")) {
				  buttonText = getLocateString(this.config.buttons[i].text.substr(1));
			  } else {
				  buttonText = this.config.buttons[i].text;
			  }
			  
			  this.mBtns[i].Text = buttonText;
			  this.mBtns[i].Click = this.config.buttons[i].onClick;
		  }
	  }
	  			      
	  // This application uses the same bitmap for both the screen and client (window).
	  this.ctx.lineWidth = 1;
	  this.ctx.strokeStyle = 'black';
      this.ctx.font = "30px Arial";	  
			
	  this.ctx.fillStyle = "white";
	  this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);	  	  
	  
	  this.canvasBackgroundImage = this.createScreenImage(true);	  	  
	  const outer = this;
      const image = new Image();
      image.onload = function () {
	      outer.ctx.drawImage(image, 0, 0);	  
      }
      image.src = this.canvasBackgroundImage;		  	  
  
      //this.startCapture();  // by default starts capturing data	  	  
	  //$("#signatureWindow").show();	
	  
  }   
  
  #onTouchStart(event) {
	  this.onDown(event);
  }
  
  #onMouseDown(event) {
	  this.onDown(event);
  }
  
  #onTouchMove(event) {
	  this.onMove(event);
  }
  
  #onMouseMove(event) {
	  this.onMove(event);
  }
  
  #onTouchEnd(event) {
	  this.onUp(event);
  }
  
  #onMouseUp(event) {
	  this.onUp(event);
  }
  
  #onTouchCancel(event) {
	  this.onUp(event);
  }
  
  #onPointerDown(event) {
	  this.onDown(event);
  }
  
  #onPointerMove(event) {
	  this.onMovePointer(event);
  }
  
  #onPointerUp(event) {
	  this.onUp(event);
  }
  
  #onPointerCancel(event) {
	  this.onUp(event);
  }
  
  #onMouseLeaveEvent(event) {
	  this.onMouseLeave(event);
  }
  
  #onMouseEnterEvent(event) {
	  this.onMouseEnter(event);
  }
  
  #onContextMenu(event) {
	  event.preventDefault();
  }
  
  #onPointerLeave(event) {
	  this.onUp(event);
  }
  
  #onPointerEnter(event) {
	  this.onDown(event);
  }
  
  
  /**
   * Start capturing data
   **/
  startCapture() {
      if (!this.isCapturing) {
		  // check if the browser support touch events
		  const touch_capable = ('ontouchstart' in document.documentElement);
		  if (touch_capable) {		
              this.touchStartID = this.#onTouchStart.bind(this);		  
			  this.mouseDownID = this.#onMouseDown.bind(this);
			  this.touchMoveID = this.#onTouchMove.bind(this);
			  this.mouseMoveID = this.#onMouseMove.bind(this);
			  this.touchEndID = this.#onTouchEnd.bind(this);
			  this.mouseUpID = this.#onMouseUp.bind(this);
			  this.touchCancelID = this.#onTouchCancel.bind(this);			  
			  
			  this.drawingCanvas.addEventListener("touchstart", this.touchStartID);
		      this.drawingCanvas.addEventListener("mousedown", this.mouseDownID);
		      this.drawingCanvas.addEventListener("touchmove", this.touchMoveID);
		      this.drawingCanvas.addEventListener("mousemove", this.mouseMoveID);
		      this.drawingCanvas.addEventListener("touchend", this.touchEndID);
		      this.drawingCanvas.addEventListener("mouseup", this.mouseUpID);	
			  this.drawingCanvas.addEventListener("touchcancel", this.touchCancelID);	
		  } else {
			  this.pointerDownID = this.#onPointerDown.bind(this);
			  this.pointerMoveID = this.#onPointerMove.bind(this);
			  this.pointerUpID = this.#onPointerUp.bind(this);
			  this.pointerCancelID = this.#onPointerCancel.bind(this);
			  this.pointerLeaveID = this.#onPointerLeave.bind(this);
			  this.pointerEnterID = this.#onPointerEnter.bind(this);
			  
	          this.drawingCanvas.addEventListener("pointerdown", this.pointerDownID);
	          this.drawingCanvas.addEventListener("pointermove", this.pointerMoveID);
	          this.drawingCanvas.addEventListener("pointerup", this.pointerUpID);
			  this.drawingCanvas.addEventListener("pointercancel", this.pointerCancelID);
			  this.drawingCanvas.addEventListener("pointerleave", this.pointerLeaveID);
			  this.drawingCanvas.addEventListener("pointerenter", this.pointerEnterID);
		  }		 
		  this.mouseLeaveID = this.#onMouseLeaveEvent.bind(this);
		  this.mouseEnterID = this.#onMouseEnterEvent.bind(this);
		  this.contextMenuID = this.#onContextMenu.bind(this);
		  
		  this.drawingCanvas.addEventListener("mouseleave", this.mouseLeaveID);		  
		  this.drawingCanvas.addEventListener("mouseenter", this.mouseEnterID);	
		  document.addEventListener('contextmenu', this.contextMenuID);
          		  
		  this.isCapturing = true;
		  this.showLoadingScreen(false);
		  this.startTimeOut();		  		  		  
	  }
  }
  
  /**
   * Stop capturing data
   **/   
  stopCapture() {
	  const touch_capable = ('ontouchstart' in document.documentElement);
	  if (touch_capable) {
	      this.drawingCanvas.removeEventListener("touchstart", this.touchStartID);
		  this.drawingCanvas.removeEventListener("mousedown", this.mouseDownID);
		  this.drawingCanvas.removeEventListener("touchmove", this.touchMoveID);
		  this.drawingCanvas.removeEventListener("mousemove", this.mouseMoveID);
		  this.drawingCanvas.removeEventListener("touchend", this.touchEndID);
		  this.drawingCanvas.removeEventListener("mouseup", this.mouseUpID);
		  this.drawingCanvas.removeEventListener("touchcancel", this.touchCancelID);
	  } else {
	      this.drawingCanvas.removeEventListener("pointerdown", this.pointerDownID);
	      this.drawingCanvas.removeEventListener("pointermove", this.pointerMoveID);
	      this.drawingCanvas.removeEventListener("pointerup", this.pointerUpID);
		  this.drawingCanvas.removeEventListener("pointercancel", this.pointerCancelID);
		  this.drawingCanvas.removeEventListener("pointerleave", this.pointerLeaveID);
		  this.drawingCanvas.removeEventListener("pointerenter", this.pointerEnterID);
	  }		 
	  this.drawingCanvas.removeEventListener("mouseleave", this.mouseLeaveID);		  
	  this.drawingCanvas.removeEventListener("mouseenter", this.mouseEnterID);		
      document.removeEventListener('contextmenu', this.contextMenuID);	  
	  
	  this.isCapturing = false;
	  this.showLoadingScreen(true);
	  this.stopTimeOut();	 
  }
  
  
  /**
   * Close the Capture Window
   **/
  async close() {
	  await this.closeWindow();
  }
  
  /**
   * Return the background Image.
   **/
  getBackgroundScreen() {
	  return this.canvasBackgroundImage;
  }
  
  /**
   * Return the button that is on the passed point.
   * @param {Point} - Coordinates of the point that are in the button
   **/
  getButton(point) {
	  for (var i = 0; i < this.mBtns.length; ++i) {
          if (this.mBtns[i].Bounds.Contains(point)) {
            return i;
          }
      }
	  return -1;
  }
  
  /**
   * Executes the button defines by its index.
   * @param {number} - Index of  the button.
   **/
  clickButton(btnIndex) {
	  if (this.mBtns.length > btnIndex) {
		  this.mBtns[btnIndex].Click();
	  }
  }
  
  showLoadingScreen(value) {
	  if (value) {
		  //this.canvas.style.display = "none";
		  this.mLoadingImageDiv.style.display = "table";
	  } else {
		  //this.canvas.style.display = "block";
		  this.mLoadingImageDiv.style.display = "none";
	  }
  }
  
  showGeneratingSignatureScreen(value) {
	  if (value) {
		  this.mGeneratingSignatureDiv.style.display = "table";
	  } else {
		  this.mGeneratingSignatureDiv.style.display = "none";
	  }
  }
  
  createWindow(width, height) {
	  if (this.config.modal) {  
          this.mModalBackground = document.createElement('div');
          this.mModalBackground.id = "modal-background";
          this.mModalBackground.className = "active";
          this.mModalBackground.style.width = "100%";
          this.mModalBackground.style.height = "100%";
		  this.mModalBackground.style.position = "fixed";
          document.getElementsByTagName('body')[0].appendChild(this.mModalBackground);
	  }
	  
      if (this.config.attachTo) {
		  const parent = document.getElementById(this.config.attachTo);
		  const offsets = parent.getBoundingClientRect();
		  
		  this.mSignatureWindow = document.createElement('div');
	      this.mSignatureWindow.setAttribute("style", "touch-action: none;z-index: 1001;");		  
	      this.mSignatureWindow.id = "signatureWindow";
	      //this.mSignatureWindow.style.position = "absolute";		  
	      this.mSignatureWindow.style.top = 0;//offsets.top;
          this.mSignatureWindow.style.left = 0;//offsets.left;
          this.mSignatureWindow.style.width = "100%";//width + "px";
          this.mSignatureWindow.style.height = "100%";//height + "px";
	      //this.mSignatureWindow.style.opacity = this.config.background.alpha;		
		  //this.mSignatureWindow.style.backgroundColor = "#ff0000";
          parent.appendChild(this.mSignatureWindow);
	
          this.mFormDiv = document.createElement('div');
		  this.mFormDiv.setAttribute("style", "touch-action: none;z-index: 1001;");		  
	      this.mFormDiv.style.position = "absolute";
          //this.mFormDiv.style.top = titleBarHeight;//(window.innerHeight / 2) - (height / 2) + "px";
          this.mFormDiv.style.width = width + "px";
          this.mFormDiv.style.height = height + "px";
          //this.mFormDiv.style.opacity = this.config.background.alpha;				  
          this.mSignatureWindow.appendChild(this.mFormDiv);

          this.canvas = document.createElement("canvas");	
          this.canvas.id = "myCanvas";
	      this.canvas.style.position = "absolute";
          this.canvas.height = this.mFormDiv.offsetHeight;
          this.canvas.width = this.mFormDiv.offsetWidth;		
		  this.canvas.style.opacity = this.config.background.alpha;				  
	      this.ctx = this.canvas.getContext("2d");
          this.mFormDiv.appendChild(this.canvas);
	
	      this.drawingCanvas = document.createElement("canvas");
	      this.drawingCanvas.id = "drawingCanvas";
	      this.drawingCanvas.style.position = "absolute";
	      this.drawingCanvas.style.top = this.canvas.style.top;
	      this.drawingCanvas.style.left = this.canvas.style.left;
		  
		  
		  const devicePixelRatio = window.devicePixelRatio || 1;
          this.drawingCanvas.height = this.canvas.height * devicePixelRatio;
          this.drawingCanvas.width = this.canvas.width * devicePixelRatio;
		  this.drawingCanvas.style.width = this.canvas.width + "px";
		  this.drawingCanvas.style.height = this.canvas.height + "px";
          this.mFormDiv.appendChild(this.drawingCanvas);      
	  } else {		  	  
	      let titleBarHeight = this.config.hasTitle ? 25 : 0;
	      let margin = 0;
	      this.mSignatureWindow = document.createElement('div');
	      this.mSignatureWindow.setAttribute("style", "touch-action: none;z-index: 1001;");
	      this.mSignatureWindow.id = "signatureWindow";
	      this.mSignatureWindow.style.position = "absolute";
		  this.mSignatureWindow.style.borderWidth = this.config.borderWidth + "px";
		  this.mSignatureWindow.style.borderStyle = "solid";
		  this.mSignatureWindow.style.borderColor = this.config.borderColor;
	      //this.mSignatureWindow.style.backgroundColor = this.config.borderColor;	
		  if (this.config.centered) {
	          this.mSignatureWindow.style.top = (window.innerHeight / 2) - (height / 2) + "px";
              this.mSignatureWindow.style.left = (window.innerWidth / 2) - (width / 2) + "px";
		  } else {
			  this.mSignatureWindow.style.top = this.config.top;
              this.mSignatureWindow.style.left = this.config.left;
		  }
          this.mSignatureWindow.style.width = (width + margin + margin) + "px";
          this.mSignatureWindow.style.height = (height+titleBarHeight + margin + margin) + "px";
	      //this.mSignatureWindow.style.opacity = this.config.background.alpha;		
          document.getElementsByTagName('body')[0].appendChild(this.mSignatureWindow);
	
	      if (this.config.hasTitle) {
	          this.mTitleBar = document.createElement("div");
	          this.mTitleBar.id = "titleBar";
		      this.mTitleBar.setAttribute("style", "display:table;padding:0;margin:0");
	          this.mTitleBar.style.width = "100%";
              this.mTitleBar.style.height = titleBarHeight+"px";
		      this.mTitleBar.style.backgroundColor = this.config.borderColor;
	          this.mTitleBar.innerHTML = '<div style="padding-left:5px;display: table-cell; vertical-align: middle;height:'+titleBarHeight+'px;">'+this.config.title+'</div>';
	          this.mSignatureWindow.appendChild(this.mTitleBar);
		  }

          this.mFormDiv = document.createElement('div');		  
	      this.mFormDiv.style.position = "absolute";
	      this.mFormDiv.style.margin = "0";
          this.mFormDiv.style.top = titleBarHeight;//(window.innerHeight / 2) - (height / 2) + "px";
          this.mFormDiv.style.width = width + "px";
          this.mFormDiv.style.height = height + "px";	
          this.mSignatureWindow.appendChild(this.mFormDiv);

          this.canvas = document.createElement("canvas");	
          this.canvas.id = "myCanvas";
	      this.canvas.style.position = "absolute";
          this.canvas.height = this.mFormDiv.offsetHeight;
          this.canvas.width = this.mFormDiv.offsetWidth;		
		  this.canvas.style.opacity = this.config.background.alpha;				  
	      this.ctx = this.canvas.getContext("2d");
          this.mFormDiv.appendChild(this.canvas);
		  
	      this.drawingCanvas = document.createElement("canvas");
	      this.drawingCanvas.id = "drawingCanvas";
	      this.drawingCanvas.style.position = "absolute";
	      this.drawingCanvas.style.top = this.canvas.style.top;
	      this.drawingCanvas.style.left = this.canvas.style.left;
          const devicePixelRatio = window.devicePixelRatio || 1;
          this.drawingCanvas.height = this.canvas.height * devicePixelRatio;
          this.drawingCanvas.width = this.canvas.width * devicePixelRatio;
		  this.drawingCanvas.style.width = this.canvas.width + "px";
		  this.drawingCanvas.style.height = this.canvas.height + "px";
          this.mFormDiv.appendChild(this.drawingCanvas);  

	      if (this.config.draggable) {
	          this.setDraggable();
		  }
	  }
	  
	  this.mLoadingImageDiv = document.createElement('div');
	  this.mLoadingImageDiv.style.display="table"
	  this.mLoadingImageDiv.style.position = "absolute";
	  this.mLoadingImageDiv.style.backgroundColor="white";
	  this.mLoadingImageDiv.style.width = "100%";
	  this.mLoadingImageDiv.style.height = "100%";
	  this.mLoadingImageDiv.innerHTML = '<div id="loadingDiv" style="padding-left:10px;display:table-cell;vertical-align:middle;"><table><tr><td><div class="loader"></div></td><td>Loading the image, this could take a few seconds...</td></tr></table></div>';
	  this.mFormDiv.appendChild(this.mLoadingImageDiv);
	  
	  this.mGeneratingSignatureDiv = document.createElement('div');
	  this.mGeneratingSignatureDiv.style.display="none"
	  this.mGeneratingSignatureDiv.style.position = "absolute";
	  this.mGeneratingSignatureDiv.style.backgroundColor="white";
	  this.mGeneratingSignatureDiv.style.width = "100%";
	  this.mGeneratingSignatureDiv.style.height = "100%";
	  this.mGeneratingSignatureDiv.style.zIndex = "10";
	  this.mGeneratingSignatureDiv.innerHTML = '<div id="generatingDiv" style="padding-left:10px;display:table-cell;vertical-align:middle;z-index:9"><table><tr><td><div class="loader"></div></td><td>Generating the signature, this could take a few seconds...</td></tr></table></div>';
	  this.mFormDiv.appendChild(this.mGeneratingSignatureDiv);
  }
	
  createScreenImage(useColor) {	  
    let canvas = document.createElement("canvas");
	canvas.width = this.canvas.width;
	canvas.height = this.canvas.height;
	
	let ctx = canvas.getContext("2d");
	ctx.lineWidth = 1;
	ctx.strokeStyle = 'black';
	
    // draw background
	//ctx.globalAlpha = this.config.background.alpha;
	
	ctx.fillStyle = useColor ? this.config.background.color : "#ffffff";
	ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);	
	
	if (this.config.background.image) {
		if (this.config.background.mode == "none") {
			ctx.drawImage(this.config.background.image, 0, 0);
		} else if (this.config.background.mode == "fit") {
			ctx.drawImage(this.config.background.image, 0, 0, canvas.width, canvas.height);
		} else if (this.config.background.mode == "center") {			
			ctx.drawImage(this.config.background.image, 
			              canvas.width/2 - this.config.background.image.width / 2, 
						  canvas.height/2 - this.config.background.image.height / 2);
		} else if (this.config.background.mode == "pattern") {			
		    const pattern = ctx.createPattern(this.config.background.image, 'repeat');
			ctx.fillStyle = pattern;
			ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);	
		}
	}		
	  
	let minFontSize = Number.MAX_SAFE_INTEGER;  
	// get the text size for the buttons
    for (let i = 0; i < this.mBtns.length; i++) {		  
	    let btn = this.mBtns[i];
	    let bounds = this.mBtns[i].Bounds;
		let textSize = this.fitTextOnCanvas(ctx, btn.Text, bounds, this.config.buttonsFont);
		if (textSize < minFontSize) {
			minFontSize = textSize;
		}
	}
	
	ctx.font = minFontSize+"px "+this.config.buttonsFont;	      
	
    let buttonsTop = 0;	
	  
	// Draw the buttons
	for (let i = 0; i < this.mBtns.length; ++i) {		  
	    let btn = this.mBtns[i];
		let bounds = this.mBtns[i].Bounds;

        ctx.fillStyle = useColor ? this.config.buttons[i].borderColor : "#000000";
	    ctx.rect(bounds.x, bounds.y, bounds.width, bounds.height);	  	  	 		
		
		ctx.fillStyle = useColor ? this.config.buttons[i].backgroundColor : "#ffffff";
	    ctx.fillRect(bounds.x, bounds.y, bounds.width, bounds.height);
		  	    
	    let metrics = ctx.measureText(btn.Text);
	    let actualHeight = metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent;
				
		let xPos = bounds.x + ((bounds.width / 2) - (ctx.measureText(btn.Text).width / 2));		
	    let yOffset = bounds.height - ((bounds.height / 2) - (actualHeight / 2));
		ctx.fillStyle = useColor ? this.config.buttons[i].textColor : "#000000";		
	    ctx.fillText(btn.Text, xPos, bounds.y + yOffset);
	    buttonsTop = bounds.height;		
		
		const button = document.createElement("button");  
		button.setAttribute("style", "position:absolute;padding:0;margin:0;");
		button.innerHTML = '<div style="line-height:'+yOffset+'px">'+btn.Text+'</div>';
		button.style.left = bounds.x+"px";
		button.style.top = bounds.y+"px";		
		button.style.width = bounds.width+"px";
		button.style.height = bounds.height+"px";
		button.style.font = minFontSize+"px "+this.config.buttonsFont;	
		button.style.color = this.config.buttons[i].textColor;
		button.style.backgroundColor = this.config.buttons[i].backgroundColor;
		button.style.border = this.config.buttons[i].borderWidth + "px solid "+this.config.buttons[i].borderColor;
        button.onclick = btn.Click;	
		
		// some touch browser wait for about 300 ms in case it is double touch. This code disables the delay
        button.addEventListener("touchend", function(e) {e.preventDefault(); btn.Click(); return false;}, false);		
		this.mFormDiv.appendChild(button);	  		       
	}	  
	
	//if (this.sigObj.isEvaluation()) {
	//    this.drawEvaluationString(getLocateString("evaluation"), ctx, this.canvas.width, this.canvas.height - buttonsTop, useColor);
	//}
	
	// draw reason
	if ((this.reason) && (this.config.reason.visible)) {
		ctx.fillStyle = useColor ? this.config.reason.color : "#000000";
		ctx.font = this.config.reason.fontSize+"px "+this.config.reason.fontFace;	    
		let metrics = ctx.measureText(this.reason);
	    //let fontHeight = metrics.fontBoundingBoxAscent + metrics.fontBoundingBoxDescent;
	    let actualHeight = metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent;		
		ctx.fillText(this.reason, this.config.reason.offsetX, actualHeight+this.config.reason.offsetY);
	}
	
	let dateOffsetY = 0;
	// draw date
	const date = new Date();
	const dateString = ('0'  + date.getHours()).slice(-2)+':'+('0'  + date.getMinutes()).slice(-2)+':'+('0' + date.getSeconds()).slice(-2)+" "+
				                 date.toLocaleString('default', { day: "2-digit", month: 'long', year: "numeric" });				
	
	//if (this.date) {
		ctx.fillStyle = useColor ? this.config.date.color : "#000000";
		ctx.font = this.config.date.fontSize+"px "+this.config.date.fontFace;	    
		let metrics = ctx.measureText(dateString);
	    //let fontHeight = metrics.fontBoundingBoxAscent + metrics.fontBoundingBoxDescent;
	    let actualHeight = metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent;
		dateOffsetY = this.canvas.height-buttonsTop-actualHeight-this.config.date.offsetY;
		if (this.config.date.visible) {
		    ctx.fillText(dateString, this.canvas.width-metrics.width-this.config.date.offsetX, dateOffsetY);		
		}
	//}
	
	// draw signatory
	let signatoryOffsetY = 0;
	if (this.signatory) {
		ctx.fillStyle = useColor ? this.config.signatory.color : "#000000";
		ctx.font = this.config.signatory.fontSize+"px "+this.config.signatory.fontFace;	    
		let metrics = ctx.measureText(this.signatory);
	    //let fontHeight = metrics.fontBoundingBoxAscent + metrics.fontBoundingBoxDescent;
	    let actualHeight = metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent;		
		signatoryOffsetY = dateOffsetY-(actualHeight*2)-this.config.signatory.offsetY;
		if (this.config.signatory.visible) {
		    ctx.fillText(this.signatory, this.canvas.width-metrics.width-this.config.signatory.offsetX, dateOffsetY-actualHeight-this.config.signatory.offsetY);
		}	
	}

    // draw line
	if ((this.config.signingLine.width > 0) && (this.config.signingLine.visible)) {
		ctx.strokeStyle = useColor ? this.config.signingLine.color : "#000000";
	    ctx.lineWidth = this.config.signingLine.width;	
		ctx.moveTo(this.config.signingLine.left, signatoryOffsetY-this.config.signingLine.width-this.config.signingLine.offsetY);
        ctx.lineTo(this.canvas.width-this.config.signingLine.right, signatoryOffsetY-this.config.signingLine.width-this.config.signingLine.offsetY);
	}
	
	
	ctx.stroke();
				
	  /*if ((this.mTablet.isSupported(com.WacomGSS.STU.Protocol.ReportId.EncryptionStatus)) ||
	    (await com.WacomGSS.STU.Protocol.ProtocolHelper.supportsEncryption(this.mTablet.getProtocol()))) {				
		  ctx.fillStyle = "black";
		  ctx.fillText("\uD83D\uDD12", 20, 50);	
	  }*/
	return canvas.toDataURL("image/jpeg"); 
  }
  
  async clearScreen() {	
    this.drawingPath = new Path2D();
    this.drawingCtx.clearRect(0, 0, this.drawingCanvas.width, this.drawingCanvas.height);	
    this.capturedPoints = new Array();
	this.clearTimeOnSurface();
  }
  
  async closeWindow() {	
    this.stopCapture();	
	this.mSignatureWindow.remove();
	
	if (this.willEngine) {
	    this.willEngine.delete();
		this.willEngine = null;
	}
	
	if (this.mModalBackground) {
		this.mModalBackground.remove();
	}
  }   
  
  async clear() {
	  this.btnClearClick();
  }
  
  async accept() {
	  this.btnOkClick();
  }
  
  async cancel() {
	  this.btnCancelClick();
  }
	
  async btnOkClick() {
	  if (this.capturedPoints.length > 0) {	      
	      let minTimeOnSurface = 0;
	      if (this.config.minTimeOnSurface) {
		      minTimeOnSurface = this.config.minTimeOnSurface;
	      }

          if (this.timeOnSurface > minTimeOnSurface) {	
		      this.stopTimeOut();
		      this.showGeneratingSignatureScreen(true);			  
              const promise = this.getCaptureData();
		      promise.then(async (value) => {
			      if (value) {
			          await this.close();
	                  this.onOkListeners.forEach(listener => listener());						  
			      } else {
				      alert("Error");
			      }
			      this.showGeneratingSignatureScreen(false);
		      });
		      promise.catch(error => {
			      alert(error);
			      this.showGeneratingSignatureScreen(false);
		      });
		  
	      }
	  }
  }
  
  async btnClearClick() {
    //if (this.capturedPoints.length > 0) {
	  await this.clearScreen();
	//}
	
	this.onClearListeners.forEach(listener => listener());
  }
  
  async btnCancelClick() {	  
      await this.close();
      this.onCancelListeners.forEach(listener => listener());
  }
  
  fitTextOnCanvas(context, text, bounds, fontface){    

    // start with a large font size
    let fontsize=300;
	let metrics;
    
    // lower the font size until the text fits the canvas
    do{
        fontsize--;
        context.font=fontsize+"px "+fontface;		
		metrics = context.measureText(text);
    }while(metrics.width>bounds.width || (metrics.actualBoundingBoxAscent + metrics.actualBoundingBoxDescent)>bounds.height)

	return fontsize;

  }

  onMouseEnter(ev) {
	  ev.preventDefault();
	  /*if ((ev.buttons == 1) && (this.mDown === "out")) {
		  this.onDown(ev);
	  } else {
		  this.mDown = "up";
	  }*/
  }
  
  onMouseLeave(ev) {
	  ev.preventDefault();
      /*if ((ev.buttons == 1) && (this.mDown === "down")) {	  
	      this.onUp(ev);
      
	      if (!this.config.onOutSide || !this.config.onOutSide()) {
	          this.mDown = "out";	    
		  }
	  }*/
  }
  
  onDown(ev) {
	  ev.preventDefault();
	  if (this.mDown === "down") return false;	  
	  let pressure = 0;
	  let x, y;
	  this.hasPressure = false;
	  let pointerType;	 	  

      if (ev instanceof PointerEvent) {
		  if (ev.buttons != 1) return false;
		  if (typeof ev.pressure !== "undefined" && (ev.pointerType === "pen" || ev.pointerType === "stu")) {
			  pressure = ev.pressure;
			  this.hasPressure = true;			  			  			  
		  }
		  x = ev.offsetX;
		  y = ev.offsetY;
		  pointerType = ev.pointerType;
		  this.pointerId = ev.pointerId;		             		  
		  
		  //intuos pro draws with button pressed on hover
	      //here we avoid it.
		  if (!this.config.allowZeroPressure) {	
              if (ev.pointerType === "pen" && ev.pressure === 0) {
				  return false;
			  }
		  }
		  
	  } else {
	      const bcr = ev.target.getBoundingClientRect();	  	  
	      if (ev.touches && ev.touches[0]) {		  
              if (typeof ev.touches[0]["force"] !== "undefined" && ev.touches[0]["force"] > 0 && ev.touches[0]["force"] < 1) {
                  pressure = ev.targetTouches[0]["force"];
			      this.hasPressure = true;
			      //if we have pressure we assume is a pen
			      pointerType = "pen";
              } else {
			      pointerType = "touch";
		      }
		      x = ev.targetTouches[0].clientX - bcr.x;;
		      y = ev.targetTouches[0].clientY - bcr.y;
			  this.pointerId = ev.targetTouches[0].identifier;
          } else {
		      pointerType = "mouse";
		      x = ev.clientX - bcr.x;;
		      y = ev.clientY - bcr.y;
			  this.pointerId = "mouse";
          }
	  }
	  
	  switch (pointerType) {
	      case "mouse" : if (!this.config.source.mouse) return; break;
		  case "touch" : if (!this.config.source.touch) return; break;
		  case "pen"   : if (!this.config.source.pen) return; break;
		  case "stu"   : if (!this.config.source.stu) return; break;
	  }
	  
	  this.mDown = "down";
	  this.disableScroll();
	  let time = Math.floor(ev.timeStamp);	  
	  if (!this.willEngine) {
	      this.willEngine = new Module.WillEngine(this.config.strokeSize, this.hasPressure);
	  }
	  this.willEngine.addPointerData(Module.Phase.BEGIN, x > 0 ? x : 0, y > 0 ? y : 0, pressure, time);
	  this.currentPath = new Path2D();
	  	  
	  const orientation = getPenOrientation(ev);
	  var point = {
		    'type': 'down',
            'x': x > 0 ? x : 0,
            'y': y > 0 ? y : 0,
            'p': pressure,
            't': time,
			'azimuth': orientation.azimuth,
			'altitude': orientation.altitude,
			'twist': orientation.twist,
            'isDown': true,
            'stroke_id': this.currentStrokeID
      };
      this.capturedPoints.push(point);
	  this.stopTimeOut();
	  this.startDown = Date.now();	  	  
	  this.drawingPoints = new Array();
	  
  }
  
  onMovePointer(ev) {
	  ev.preventDefault();	  	  	  
	  if (this.mDown === "up") return false;
	  let lastEvent;
      if (typeof ev.getCoalescedEvents === "function") {
	      const events = ev.getCoalescedEvents();
          for (const myEvent of events) {
			  if (!lastEvent ||
			      myEvent.offsetX != lastEvent.offsetX ||
			      myEvent.offsetY != lastEvent.offsetY ||
				  myEvent.timeStamp != lastEvent.timeStamp) {
			      if (!this.onMove(myEvent)) {
					  return false;
				  }					  
			  }
			  lastEvent = myEvent;
			  
		  }
		  // in case some browser does not put the current event on the coalescedEvents
          if (!lastEvent ||
		      event.offsetX != lastEvent.offsetX ||
		      event.offsetY != lastEvent.offsetY ||
			  event.timeStamp != lastEvent.timeStamp) {
			      if (!this.onMove(event)) {
					  return false;
				  }					  
		  }			  
	  } else {
	      this.onMove(event);
	  }
  }
  
  onMove(ev) {	
	  ev.preventDefault();
	  if (this.mDown === "up") return false;
	  
	  let pressure = 0;
	  let x, y;	  
	  
	  if (ev instanceof PointerEvent) {
		  if (typeof ev.pressure !== "undefined" && (ev.pointerType === "pen" || ev.pointerType === "stu")) {
			  pressure = ev.pressure;
		  }
		  x = ev.offsetX;
		  y = ev.offsetY;
		  if (this.pointerId !== "mouse" && this.pointerId !== ev.pointerId) {
			  return false;
		  }
	  } else {	  
	      const bcr = ev.target.getBoundingClientRect();	  
	      if (ev.touches && ev.touches[0]) {
              if (typeof ev.touches[0]["force"] !== "undefined" && ev.touches[0]["force"] > 0) {
                  pressure = ev.touches[0]["force"];
		      }
		      x = ev.targetTouches[0].clientX - bcr.x;
		      y = ev.targetTouches[0].clientY - bcr.y;		  		  
			  if (this.pointerId !== ev.targetTouches[0].identifier) {
				  return false;
			  }
          } else {
		      x = ev.clientX - bcr.x;
		      y = ev.clientY - bcr.y;			  
			  if (this.pointerId !== "mouse") {
				  return false;
			  }
          }	  
	  }
	  
	  // onpointerleave is not working fine with pen
	  // so we handle it here.
      if (x > this.drawingCanvas.width || 
	      y > this.drawingCanvas.height ||
		  x < 0 ||
		  y < 0) {		
		  if (this.mDown === "down") {			  		      
		      this.onUp(ev);		
		      if (!this.config.onOutSide || !this.config.onOutSide()) {
				  this.mDown = "out";
		      }			  
		  }          
          return false;		  
	  } else if (this.mDown === "out") {
		  this.onDown(ev);
		  return false;
	  } 	  	  	  
	  
	  let time = Math.floor(ev.timeStamp);	  
	  const orientation = getPenOrientation(ev);
	  var point = {
		    'type': "move",
            'x': x > 0 ? x : 0,
            'y': y > 0 ? y : 0,
            'p': pressure,
            't': time,
			'azimuth': orientation.azimuth,
			'altitude': orientation.altitude,
			'twist': orientation.twist,
            'isDown': true,
            'stroke_id': this.currentStrokeID
      };
	  this.capturedPoints.push(point);
	  if (this.willEngine) {
	      this.willEngine.addPointerData(Module.Phase.UPDATE, x > 0 ? x : 0, y > 0 ? y : 0, pressure, time);
	  
	      const polygon = this.willEngine.getLastPolygon();
	      const polygonPoints = polygon.getPoints();
	      if (polygonPoints.size() > 0) {
	          for (let i=0; i< polygonPoints.size(); i++) {
		          const polygonPoint = polygonPoints.get(i);
		          if (polygonPoint.pointType == Module.PolygonPointType.MOVE) {
			          this.currentPath.moveTo(polygonPoint.x, polygonPoint.y); 
		          } else {			  
			          this.currentPath.lineTo(polygonPoint.x, polygonPoint.y); 
				  }
              }	
	      }
          this.currentPath.closePath();		  
	      polygon.delete();
		  this.repaintScreen();
	  }	
	  
      return true;	  
  }
  
  onUp(ev) {
	  ev.preventDefault();
	  if (this.mDown !== "down") return false;	  
	  
	  let pressure = 0;
	  let x, y;
	  
	  if (ev instanceof PointerEvent) {
		  if (typeof ev.pressure !== "undefined" && (ev.pointerType === "pen" || ev.pointerType === "stu")) {
			  pressure = ev.pressure;
		  }
		  x = ev.offsetX;
		  y = ev.offsetY;
	  } else {	  
	      const bcr = ev.target.getBoundingClientRect();	  
	      if (this.mDown === "out") {
		      if (ev.touches && ev.touches[0]) {
                  if (typeof ev.touches[0]["force"] !== "undefined" && ev.touches[0]["force"] > 0) {
                      pressure = ev.touches[0]["force"];
			      }
		          x = ev.targetTouches[0].clientX - bcr.x;
		          y = ev.targetTouches[0].clientY - bcr.y;
              } else {
		          x = ev.clientX - bcr.x;
		          y = ev.clientY - bcr.y;
              }
	      } else {	  	  
	          if (ev.changedTouches && ev.changedTouches[0]) {
		          // on up event does not have touches coordinates
		          if (ev.changedTouches[0]["force"] !== "undefined" && ev.changedTouches[0]["force"] > 0) {
                      pressure = ev.changedTouches[0]["force"];
			      }
		          x = ev.changedTouches[0].clientX - bcr.x;;
		          y = ev.changedTouches[0].clientY - bcr.y;
              } else {
		          x = ev.clientX - bcr.x;
		          y = ev.clientY - bcr.y;
		      }
		  }
      }
	  
	  let time = Math.floor(ev.timeStamp);	  
	  const orientation = getPenOrientation(ev);
	  var point = {
		    'type': 'up',
            'x': x > 0 ? x : 0,
            'y': y > 0 ? y : 0,
            'p': 0, 
            't': time,
			'azimuth': orientation.azimuth,
			'altitude': orientation.altitude,
			'twist': orientation.twist,
            'isDown': false,
            'stroke_id': this.currentStrokeID
      };
      this.capturedPoints.push(point);		  
	  this.startTimeOut();	  
	  this.addTimeOnSurface(Date.now() - this.startDown);
	  this.enableScroll();	  	  
	  	  
	  if (this.willEngine) {		  
	      this.willEngine.addPointerData(Module.Phase.END, x > 0 ? x : 0, y > 0 ? y : 0, 0, time);	  
	      this.willEngine.getLastPolygon().delete(); //this call is necessary
	  
	      this.currentPath = null;
	      const polygon = this.willEngine.getStroke();
	      const polygonPoints = polygon.getPoints();
	      for (let i=0; i< polygonPoints.size(); i++) {
		      const polygonPoint = polygonPoints.get(i);
		      if (polygonPoint.pointType == Module.PolygonPointType.MOVE) {
			      this.drawingPath.moveTo(polygonPoint.x, polygonPoint.y); 
		      } else {			  
			      this.drawingPath.lineTo(polygonPoint.x, polygonPoint.y); 
		      }
	      }		  
	      this.repaintScreen(true);
	      polygon.delete();	      
	  }
	  
	  this.mDown = "up";
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
		
        for (let index = 0; index < this.capturedPoints.length; index++) {
		    if (!this.capturedPoints[index].isDown && !hasDown) {
				// the signature starts with the first pen down, so the hover
				// points before first down are ignored.
			    continue;
		    }
		
		    hasDown = true;
		
            if ((isDown && !this.capturedPoints[index].isDown) || (!isDown && this.capturedPoints[index].isDown)) {			
                isDown = this.capturedPoints[index].isDown;
                //Move the current stroke data into the strokes array
                strokeVector.push_back({'points': currentStroke});
                currentStroke.delete();
                currentStroke = new Module.PointVector();
                currentStrokeID++;
            }	
			
            var point = {
                'x': Math.floor(this.capturedPoints[index].x),
                'y': Math.floor(this.capturedPoints[index].y),
                'p': Math.floor(this.capturedPoints[index].p*1000), // convert from 0-1 range to 0-1000 without decimals
                't': this.capturedPoints[index].t,
			    'azimuth': this.capturedPoints[index].azimuth,
				'altitude': this.capturedPoints[index].altitude,
			    'twist': this.capturedPoints[index].twist,			
                'is_down': (this.capturedPoints[index].type == "down" || this.capturedPoints[index].type == "move"),
                'stroke_id': currentStrokeID
            };
			
			//console.log(JSON.stringify(point));
		
            currentStroke.push_back(point);	
			
        }	

        const dimensions = this.mmToPx(1000, 1000);		
	
	    //Create capture area character
        var device = {
            'device_max_X': this.canvas.width,
            'device_max_Y': this.canvas.height,
            'device_max_P': 1000,
            'device_pixels_per_m_x': dimensions.width,
		    'device_pixels_per_m_y': dimensions.height,
            'device_origin_X': 0,
            'device_origin_Y': 1,
			'device_unit_pixels': true
		}	

        const timeResolution = 1000;		
        const myPromise = new Promise((resolve, reject) => {
			try {
                const promise = this.sigObj.generateSignature(this.signatory, this.reason, this.where, this.integrityType, this.documentHash, strokeVector, device, this.osInfo, this.digitizerInfo, this.nicInfo, timeResolution);
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
		    } catch(exception) {
		        strokeVector.delete();
                currentStroke.delete();
				reject(exception);
		    }
		});			    	

        return myPromise;		
    }
	
	mmToPx(width, height) {
	    const el = document.createElement('div');
	    el.style = 'width: '+width+'mm; height:'+height+'mm;'
	    document.body.appendChild(el);
	    const pxWidth = el.offsetWidth;
	    const pxHeight = el.offsetHeight;
	    document.body.removeChild(el);
	    return {width:pxWidth, height:pxHeight};
    }
 	
	drawEvaluationString(evaluationString, context, width, height, useColor) {
		return true;
		evaluationString = " "+evaluationString+" ";
        // get the hypotenuse, as we are going to write the text in diagonal
	    const hypotenuse = Math.sqrt(width*width + height*height);

        // then get the desire text size
        let testTextSize = 300.0;    
        context.font = "300px verdana";
		let textMetrics = context.measureText(evaluationString);
	    let desiredTextSize = (testTextSize * hypotenuse / textMetrics.width);

        // we need to reduce this text according to the height size
        context.font = desiredTextSize+"px verdana";
        textMetrics = context.measureText(evaluationString);   

        // find the rotation angle
        const angle = Math.atan(height/width);
    
        // get the new width taking on account the height
	    const newWidth = hypotenuse - ((textMetrics.actualBoundingBoxAscent + textMetrics.actualBoundingBoxDescent) * Math.cos(-angle));

        // Calculate the desired size as a proportion of our testTextSize.
        desiredTextSize = desiredTextSize * newWidth / textMetrics.width;
        context.font = desiredTextSize+"px verdana";
        textMetrics = context.measureText(evaluationString);   

        context.save();
        context.fillStyle = useColor ? "LightGray" : "#000000";		
        context.translate(width/2, height/2);		
        context.rotate(-angle);
        context.fillText(evaluationString, -textMetrics.width/2, (textMetrics.actualBoundingBoxAscent + textMetrics.actualBoundingBoxDescent)/2);
        context.restore();
    }
	
	startTimeOut() {
		if ((this.config.timeOut) && (this.config.timeOut.enabled) && (this.config.timeOut.onTimeOut)) {
			this.stopTimeOut();
	        this.timeOutInterval = setInterval(this.timeOutCallback.bind(this), this.config.timeOut.time);
	    }
	}
	
	stopTimeOut() {
		if (this.timeOutInterval) {
		    clearInterval(this.timeOutInterval);
		    this.timeOutInterval = null;
	    }
	}
	
	timeOutCallback() {
		if (this.timeOutInterval) {
		    clearInterval(this.timeOutInterval);		
			this.timeOutInterval = null;
		    this.config.timeOut.onTimeOut(this.timeOnSurface);
		}
	}
	
	addTimeOnSurface(time) {
		this.timeOnSurface += time;
	}
	
	clearTimeOnSurface() {
		this.timeOnSurface = 0;
	}
	
	setDraggable() {
		const self = this;
		const titleBar = document.getElementById("titleBar");		
		titleBar.style.cursor = "move";
		const signatureWindow = document.getElementById("signatureWindow");
		titleBar.addEventListener('pointerdown', function(e) {
	        e.preventDefault();
	        this.initX = signatureWindow.offsetLeft;
	        this.initY = signatureWindow.offsetTop;
	        this.firstX = e.pageX;
	        this.firstY = e.pageY;

	        titleBar.addEventListener('pointermove', self.dragIt, false);

	        window.addEventListener('pointerup', function() {
		        titleBar.removeEventListener('pointermove', self.dragIt, false);
	        }, false);
			
        }, false);
	}
	
	dragIt(e) {
		const signatureWindow = document.getElementById("signatureWindow");
	    signatureWindow.style.left = this.initX+e.pageX-this.firstX + 'px';
	    signatureWindow.style.top = this.initY+e.pageY-this.firstY + 'px';
    }
	
	//firefox seems to scroll instead of drawing when using pen
	//so we use this function to disable the scroll while drawing
	disableScroll() {
		if (navigator.userAgent.indexOf('Firefox') !== -1) {
		    document.body.style.overflowY = "hidden";
		}
	}
	
	enableScroll() {
        if (navigator.userAgent.indexOf('Firefox') !== -1) {
            document.body.style.overflowY = "auto";
		}
    }
	
	repaintScreen(force) {
		if (!this.requestedDrawing || force) {
			this.requestedDrawing = true;

			this.drawingCtx.clearRect(0, 0, this.drawingCanvas.width, this.drawingCanvas.height);	
		    this.drawingCtx.fill(this.drawingPath);
			
		    if (this.currentPath) {				
				this.drawingCtx.fill(this.currentPath);							
		    }		
            
			requestAnimationFrame(() => (this.requestedDrawing = false));					
		}	        		
	}	
}
