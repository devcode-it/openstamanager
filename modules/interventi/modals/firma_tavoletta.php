<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../../core.php';

$intervento = Modules\Interventi\Intervento::find($id_record);

echo '
<script src="'.$rootdir.'/assets/dist/js/wacom.min.js"></script>';

echo '
<form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record,'" method="post" id="form-firma">
    <input type="hidden" name="op" value="firma">
    <input type="hidden" name="backto" value="record-edit">

    <div class="row">
        <div class="col-md-12">
            <div id="signature-pad" class="signature-pad" style="margin:auto;"></div>
        </div>
        <div class="col-md-12 text-center">
            <img src="" id="image-signature" style="display:none;text-align:center;">
            <input type="hidden" name="firma_base64" id="firma_base64" value="">   
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-12 text-right" id="btn-group-firma" style="display:none;">
            <a type="button" class="btn btn-danger" onclick="resetFirma();" >
                <i class="fa fa-trash"></i> '.tr('Cancella firma').'
            </a>
            <button type="submit" class="btn btn-success" data-action="save">
                <i class="fa fa-check"></i> '.tr('Salva firma').'
            </button>
        </div>
    </div>

</form>
<div class="clearfix"></div>

<script type="text/javascript">

    const licence = "'.setting('Licenza Wacom SDK').'";
    var mSigObj;		
    var documentHash;
    var backgroundImage;
    var sigCaptDialog
    var stuCapDialog;
    
    // This var will store the public and private keys for encryption.
    // Please note that this is only a demostration, but on a production application
    // for security reasons the private key should not be stored in a global variable.
    var encryptionKeys;

    Module.onRuntimeInitialized = _ => {	            
        documentHash = new Module.Hash(Module.HashType.None);
        mSigObj = new Module.SigObj();	
        mSigObj.setLicence(licence);
    }

    function capture() {

        let captureDiv = document.getElementById("signature-pad");													    
        captureDiv.style.width = "500px";
        captureDiv.style.height = "300px";

        sigCaptDialog = null;
        const config = generateConfig();

        stuCapDialog = new StuCaptDialog(config);
        stuCapDialog.addEventListener("ok", function() {
            renderSignature();
        });				
        stuCapDialog.open(mSigObj, '.prepare($intervento->anagrafica->ragione_sociale).', "'.tr('Firma').'", [], Module.KeyType.SHA512, documentHash);				
    }

    function generateConfig() {
        const config = {};
        config.useWill = 1;
        config.strokeSize = 2;
        config.strokeColor = "#000000";
        config.width = 500;
        config.height = 300;
        config.left = 0;
        config.top = 0;
        config.centered = 1;
        config.title = "'.tr('Firma').'";
        config.borderColor = "#000000";
        config.borderWidth = 3;
        config.hasTitle = 1;
        
        config.signatory = {visible:1,
                            fontFace:"Verdana",
                            fontSize:parseInt(20),
                            offsetX:parseInt(30),
                            offsetY:parseInt(5),
                            color:"#000000"
                            };
                            
        config.reason = {visible:1,
                            fontFace:"Verdana",
                            fontSize:parseInt(20),
                            offsetX:parseInt(5),
                            offsetY:parseInt(10),
                            color:"#000000"
                        };				   
                        
        config.date = {visible:1,
                        fontFace:"Verdana",
                        fontSize:parseInt(20),
                        offsetX:parseInt(30),
                        offsetY:parseInt(20),
                        color:"#000000"
                        };				   				
                        
        config.signingLine = {visible:1,
                                left:parseInt(30),
                                right:parseInt(30),
                                width:parseInt(2),
                                offsetY:parseInt(5),
                                color:"#D3D3D3"
                            };				   							  
        
        config.buttonsFont = "Arial";
        config.buttons = [];	

        config.buttons.push({text:"Cancella", 
                            textColor:"#000000", 
                            backgroundColor:"#e7e7e7", 
                            borderColor:"#cccccc", 
                            borderWidth:parseInt(1),
                            onClick:eval("clear"),
                        });

        config.buttons.push({text:"Annulla", 
                            textColor:"#000000", 
                            backgroundColor:"#e7e7e7", 
                            borderColor:"#cccccc", 
                            borderWidth:parseInt(1),
                            onClick:eval("cancel"),
                        });

        config.buttons.push({text:"Conferma", 
                            textColor:"#000000", 
                            backgroundColor:"#e7e7e7", 
                            borderColor:"#cccccc", 
                            borderWidth:parseInt(1),
                            onClick:eval("accept"),
                        });

        //const comboSizeModes = "fixed";
        config.sizeMode = "fixed";

        config.attachTo = "signature-pad";	
        
        config.modal = 1;
        config.draggable = 1;
        
        //const comboTools = "pen";	
        const inkColor = "#000F55";
        config.will = {tool:tools["pen"], color:inkColor};
        
        //const comboBackgroundMode = "fit";	
        config.background = {color:"#ffffff", 
                                alpha:100*0.01,
                                mode:"fit"
                            };
        
        config.minTimeOnSurface = parseInt(300);

        config.timeOut = {enabled:true};
        config.timeOut.time = parseInt(15*1000);
        config.timeOut.onTimeOut = timeOutCallback;
        
        return config;
    }			

    function timeOutCallback(timeOnSurface) {
        const minTimeOnSurface = parseInt(300);
        if (minTimeOnSurface < timeOnSurface) {
            accept();
        } else {
            cancel();			
        }
    }		

    function clear() {
        if (stuCapDialog) {
            stuCapDialog.clear();
        }
        
        if (sigCaptDialog) {
            sigCaptDialog.clear();
        }
    }
    
    function cancel() {
        if (stuCapDialog) {
            stuCapDialog.cancel();
        }
        
        if (sigCaptDialog) {
            sigCaptDialog.cancel();
        }

        $(".close").trigger("click");
    }
    
    function accept() {
        if (stuCapDialog) {
            stuCapDialog.accept();
        }
        
        if (sigCaptDialog) {
            if (stuCapDialog) {
                sigCaptDialog.cancel();
            } else {
                sigCaptDialog.accept();
            }
            
        }
    }

    async function renderSignature() {
        const image = await renderSignatureImage();
                        
        $("#signature-pad").hide();
        $("#image-signature").show();
        $("#btn-group-firma").show();

        document.getElementById("image-signature").src = image;	
        document.getElementById("firma_base64").value = image;        
    }

    async function renderSignatureImage() {
        // calculate the size
        let renderWidth = 500;
        let renderHeight = 300;
        const isRelative = 1;
        
        let renderFlags = 0x400000;
        if (isRelative) {				
            renderFlags |= 0x2000000;
            const sx = (96/25.4)*2;
            renderWidth = Math.floor(mmToPx(mSigObj.getWidth(true)/100) + sx);
            renderHeight = Math.floor(mmToPx(mSigObj.getHeight(true)/100) + sx);
        } else {
            if (isNaN(renderWidth) || renderWidth <= 0) {
                if (isNaN(renderHeight) || renderHeight <= 0) {
                    // it takes the original size							
                    renderWidth = mmToPx(mSigObj.getWidth(false)/100);
                    renderHeight = mmToPx(mSigObj.getHeight(false)/100);
                } else {
                    // it takes the size proportional to the height
                    const originalRenderWidth = mmToPx(mSigObj.getWidth()/100);
                    const originalRenderHeight = mmToPx(mSigObj.getHeight()/100);
                    renderWidth = (originalRenderWidth/originalRenderHeight)*renderHeight;
                }
            } else if (isNaN(renderHeight) || renderHeight <= 0) {
                // it takes the size proportinal to the width
                const originalRenderWidth = mmToPx(mSigObj.getWidth()/100);
                const originalRenderHeight = mmToPx(mSigObj.getHeight()/100);
                renderHeight = (originalRenderHeight/originalRenderWidth)*renderWidth;
            }
        
            renderWidth = Math.floor(renderWidth);
            renderHeight = Math.floor(renderHeight);				
            renderWidth += renderWidth % 4;
        }															
        
        const backgroundColor = "'.setting('Sfondo firma tavoletta Wacom').'";
        
        if (isRelative) {
            renderWidth = -96; //dpi
            renderHeight = -96;
        }
        
        const inkColor = "#000F55";
        const comboTools = "pen";					
        const inkTool = tools["pen"];
        const image = await mSigObj.renderBitmap(renderWidth, renderHeight, "image/png", inkTool, inkColor, backgroundColor, 0, 0, renderFlags);				
        return image;				
    }

    function mmToPx(mm) {
        var dpr = window.devicePixelRatio;
        var inch = 25.4; //1inch = 25.4 mm
        var ppi = 96;	
        return ((mm/inch)*ppi)/dpr;
    }
    
    function pxToMm(px) {
        var dpr = window.devicePixelRatio;
        var inch = 25.4; //1inch = 25.4 mm
        var ppi = 96;	
        return ((px*dpr)/ppi)*inch;
    }
    
    function pxToInches(px) {
        return px/96;
    }

    $("#modals").on("hidden.bs.modal", function () {
        location.reload();
    });

    function resetFirma(){
        document.getElementById("image-signature").src = null;	

        $("#signature-pad").show();
        $("#image-signature").hide();
        $("#btn-group-firma").hide();

        capture();
    }

    setTimeout(function() {
        capture();
    },1000);
        
</script>';
