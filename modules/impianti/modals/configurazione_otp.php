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

$id_module = get('id_module');
$id_record = get('id_record');

// Recupera informazioni dell'impianto
$record = $dbo->fetchOne('SELECT * FROM my_impianti WHERE id = ?', [$id_record]);

// Verifica se esiste già un token per questo impianto
$existing_token = $dbo->fetchOne('SELECT * FROM zz_otp_tokens WHERE id_module_target = ? AND id_record_target = ? AND enabled = 1', [$id_module, $id_record]);

$qrcode_print_info = $dbo->fetchOne('SELECT id, options FROM zz_prints WHERE directory = "qrcode"');
$qrcode_print = $qrcode_print_info['id'];

// Recupera le dimensioni dell'etichetta QR Code
$qrcode_size = '';
if (!empty($qrcode_print_info['options'])) {
    $options = json_decode((string) $qrcode_print_info['options'], true);
    if (is_array($options)) {
        if (isset($options['width']) && isset($options['height'])) {
            $qrcode_size = ' ('.$options['width'].'x'.$options['height'].'mm)';
        } elseif (isset($options['format']) && is_array($options['format']) && count($options['format']) >= 2) {
            $qrcode_size = ' ('.$options['format'][0].'x'.$options['format'][1].'mm)';
        }
    }
}
?>

<?php if ($existing_token) { ?>
    <!-- Token esistente -->
    <div class="container-fluid">        
        <div class="row">
            <div class="col-lg-8 col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fa fa-info-circle"></i> <?php echo tr('Informazioni Token'); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong><?php echo tr('Descrizione'); ?>:</strong></div>
                            <div class="col-sm-8"><?php echo $existing_token['descrizione']; ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong><?php echo tr('URL Accesso'); ?>:</strong></div>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?php echo base_url().'/?token='.$existing_token['token']; ?>" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('<?php echo base_url().'/?token='.$existing_token['token']; ?>')" title="<?php echo tr('Copia negli appunti'); ?>">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4"><strong><?php echo tr('Valido dal'); ?>:</strong></div>
                            <div class="col-sm-8"><span class="badge badge-info"><?php echo $existing_token['valido_dal'] ?: tr('Nessuna scadenza'); ?></span></div>
                        </div>
                        <div class="row mb-0">
                            <div class="col-sm-4"><strong><?php echo tr('Valido al'); ?>:</strong></div>
                            <div class="col-sm-8"><span class="badge badge-info"><?php echo $existing_token['valido_al'] ?: tr('Nessuna scadenza'); ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fa fa-qrcode"></i> <?php echo tr('QR Code'); ?>
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <img src="<?php echo base_url(); ?>/modules/impianti/modals/qrcode_generator.php?url=<?php echo urlencode(base_url().'/?token='.$existing_token['token']); ?>" 
                                 alt="QR Code" class="img-fluid" style="max-width: 200px; border: 2px solid #dee2e6; border-radius: 8px; padding: 15px; background: white;">
                        </div>
                        <div class="btn-group-vertical w-100" role="group">
                            <button type="button" class="btn btn-primary mb-2" onclick="printQRCode('<?php echo base_url().'/?token='.$existing_token['token']; ?>', <?php echo $existing_token['id']; ?>)">
                                <i class="fa fa-print"></i> <?php echo tr('Stampa QR Code').$qrcode_size; ?>
                            </button>
                            <button type="button" class="btn btn-warning" onclick="editToken(<?php echo $existing_token['id']; ?>)">
                                <i class="fa fa-edit"></i> <?php echo tr('Modifica Token'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
<?php } else { ?>
    <!-- Wizard per nuovo token -->
    
    <!-- Progress Steps -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-center">
                    <div class="step-progress">
                        <div class="step-item active" id="progress-step-1">
                            <div class="step-circle">1</div>
                            <div class="step-label"><?php echo tr('Configurazione'); ?></div>
                        </div>
                        <div class="step-connector"></div>
                        <div class="step-item" id="progress-step-2">
                            <div class="step-circle">2</div>
                            <div class="step-label"><?php echo tr('Validità'); ?></div>
                        </div>
                        <div class="step-connector"></div>
                        <div class="step-item" id="progress-step-3">
                            <div class="step-circle">3</div>
                            <div class="step-label"><?php echo tr('Completato'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .step-progress {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
        }
        
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .step-item.active .step-circle {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .step-item.completed .step-circle {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }
        
        .step-label {
            margin-top: 8px;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
            min-width: 80px;
        }
        
        .step-item.active .step-label {
            color: #007bff;
            font-weight: 600;
        }
        
        .step-item.completed .step-label {
            color: #28a745;
        }
        
        .step-connector {
            width: 60px;
            height: 2px;
            background-color: #e9ecef;
            margin: 0 10px;
            margin-bottom: 20px;
        }
        
        .step-connector.active {
            background-color: #007bff;
        }
        
        .step-connector.completed {
            background-color: #28a745;
        }
        </style>
    
    <form id="otp-wizard-form">
        <input type="hidden" name="id_module" value="<?php echo $id_module; ?>">
        <input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
        
        <!-- Step 1: Configurazione -->
        <div class="wizard-step" id="step1" style="display: block;">            
            <div class="row">
                <div class="col-md-12">
                    {[ "type": "text", "label": "<?php echo tr('Descrizione del token'); ?>", "name": "descrizione", "required": 1, "value": "Accesso sola lettura - <?php echo $record['nome']; ?> - <?php echo $record['matricola']; ?>" ]}
                </div>
            </div>
        </div>
        
        <!-- Step 2: Validità -->
        <div class="wizard-step" id="step2" style="display: none;">
            <div class="row">
                <div class="col-md-12">
                    {[ "type": "select", "label": "<?php echo tr('Tipo di accesso'); ?>", "name": "tipo_accesso", "required": 1, "value": "token", "values": "list=\"token\":\"<?php echo tr('Accesso diretto'); ?>\",\"otp\":\"<?php echo tr('Accesso con OTP via email'); ?>\"" ]}
                </div>
            </div>
            
            <div class="row" id="email-field" style="display: none;">
                <div class="col-md-12">
                    {[ "type": "email", "label": "<?php echo tr('Email per OTP'); ?>", "name": "email", "value": "" ]}
                </div>
            </div>
            
            <div class="alert alert-info mt-3">
                <i class="fa fa-info-circle"></i>
                <?php echo tr('Se le date non vengono inserite, il token non avrà scadenza'); ?>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "date", "label": "<?php echo tr('Valido dal'); ?>", "name": "valido_dal", "value": "" ]}
                </div>
                <div class="col-md-6">
                    {[ "type": "date", "label": "<?php echo tr('Valido al'); ?>", "name": "valido_al", "value": "" ]}
                </div>
            </div>
        </div>
        
        <!-- Step 3: Completato -->
        <div class="wizard-step" id="step3" style="display: none;">            
            <div id="token-result">
                <!-- Il contenuto verrà popolato dinamicamente -->
            </div>
        </div>
    </form>
    
<?php } ?>

<div class="modal-footer">
    <?php if (!$existing_token) { ?>
        <div id="wizard-navigation">
            <button type="button" class="btn btn-secondary" id="prev-btn" style="display: none;" onclick="prevStep()">
                <i class="fa fa-arrow-left"></i> <?php echo tr('Precedente'); ?>
            </button>
            <button type="button" class="btn btn-primary" id="next-btn" onclick="nextStep()">
                <?php echo tr('Avanti'); ?> <i class="fa fa-arrow-right"></i>
            </button>
            <button type="button" class="btn btn-success" id="create-btn" style="display: none;" onclick="createToken()">
                <i class="fa fa-plus"></i> <?php echo tr('Crea Token'); ?>
            </button>
            <button type="button" class="btn btn-secondary" id="close-btn" style="display: none;" data-dismiss="modal">
                <?php echo tr('Chiudi'); ?>
            </button>
        </div>
    <?php } else { ?>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <?php echo tr('Chiudi'); ?>
        </button>
    <?php } ?>
</div>

<script>
// Evita la ridichiarazione se le variabili esistono già
if (typeof window.currentStep === 'undefined') {
    window.currentStep = 1;
}
if (typeof window.totalSteps === 'undefined') {
    window.totalSteps = 3;
}

function updateProgress() {
    // Update step progress indicators
    $('.step-item').removeClass('active completed');
    $('.step-connector').removeClass('active completed');
    
    for (let i = 1; i <= window.totalSteps; i++) {
        if (i < window.currentStep) {
            $('#progress-step-' + i).addClass('completed');
            if (i < window.totalSteps) {
                $('#progress-step-' + i).next('.step-connector').addClass('completed');
            }
        } else if (i === window.currentStep) {
            $('#progress-step-' + i).addClass('active');
            if (i > 1) {
                $('#progress-step-' + (i-1)).next('.step-connector').addClass('completed');
            }
        }
    }
    
    // Show/hide steps
    $('.wizard-step').hide();
    $('#step' + window.currentStep).show();
    
    // Update button visibility
    $('#prev-btn').toggle(window.currentStep > 1 && window.currentStep !== 3);
    $('#next-btn').toggle(window.currentStep < window.totalSteps && window.currentStep !== 2);
    $('#create-btn').toggle(window.currentStep === 2);
    $('#close-btn').toggle(window.currentStep === 3);
}

function nextStep() {
    if (window.currentStep < window.totalSteps) {
        if (validateCurrentStep()) {
            window.currentStep++;
            updateProgress();
        }
    }
}

function prevStep() {
    if (window.currentStep > 1) {
        window.currentStep--;
        updateProgress();
    }
}

function validateCurrentStep() {
    if (window.currentStep === 1) {
        const descrizione = $('input[name="descrizione"]').val();
        if (!descrizione.trim()) {
            toastr.error('<?php echo tr('Inserisci una descrizione per il token'); ?>');
            return false;
        }
    }
    return true;
}

function createToken() {
    const formData = new FormData($('#otp-wizard-form')[0]);
    
    $.ajax({
        url: 'actions.php',
        type: 'POST',
        data: {
            op: 'create_otp_token',
            id_module: formData.get('id_module'),
            id_record: formData.get('id_record'),
            descrizione: formData.get('descrizione'),
            tipo_accesso: formData.get('tipo_accesso'),
            email: formData.get('email'),
            valido_dal: formData.get('valido_dal'),
            valido_al: formData.get('valido_al')
        },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                console.log('Token info ricevuto:', data.token_info);
                showTokenResult(data.token_info);
                window.currentStep = 3;
                updateProgress();
                toastr.success(data.message);
            } else {
                toastr.error(data.message);
            }
        },
        error: function() {
            toastr.error('<?php echo tr('Errore durante la creazione del token'); ?>');
        }
    });
}

function showTokenResult(tokenInfo) {
    // Costruisce l'URL del QR code
    const qrCodeUrl = '<?php echo base_url(); ?>/modules/impianti/modals/qrcode_generator.php?url=' + encodeURIComponent(tokenInfo.url);
    
    const html = `
        <div class="container-fluid">          
            <div class="row">
                <div class="col-lg-8 col-md-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-info-circle"></i> <?php echo tr('Informazioni Token'); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong><?php echo tr('Descrizione'); ?>:</strong></div>
                                <div class="col-sm-8">${tokenInfo.descrizione}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong><?php echo tr('URL Accesso'); ?>:</strong></div>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="${tokenInfo.url}" readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('${tokenInfo.url}')" title="<?php echo tr('Copia negli appunti'); ?>">
                                                <i class="fa fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-sm-4"><strong><?php echo tr('Valido dal'); ?>:</strong></div>
                                <div class="col-sm-8"><span class="badge badge-info">${tokenInfo.valido_dal}</span></div>
                            </div>
                            <div class="row mb-0">
                                <div class="col-sm-4"><strong><?php echo tr('Valido al'); ?>:</strong></div>
                                <div class="col-sm-8"><span class="badge badge-info">${tokenInfo.valido_al}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-5">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">
                                <i class="fa fa-qrcode"></i> <?php echo tr('QR Code'); ?>
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <img src="${qrCodeUrl}" alt="QR Code" class="img-fluid" style="max-width: 200px; border: 2px solid #dee2e6; border-radius: 8px; padding: 15px; background: white;">
                            </div>
                            <div class="btn-group-vertical w-100" role="group">
                                <button type="button" class="btn btn-primary mb-2" onclick="printQRCode('${tokenInfo.url}', ${tokenInfo.id})">
                                    <i class="fa fa-print"></i> <?php echo tr('Stampa QR Code').$qrcode_size; ?>
                                </button>
                                <button type="button" class="btn btn-warning" onclick="editToken(${tokenInfo.id})">
                                    <i class="fa fa-edit"></i> <?php echo tr('Modifica Token'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#token-result').html(html);
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        toastr.success('<?php echo tr('Copiato negli appunti'); ?>');
    });
}

function printQRCode(url, tokenId) {

    printId = <?php echo $qrcode_print; ?>

    try {
        var data = JSON.parse(response);
        var printId = data && data.length > 0 ? data[0].id : 56; // Fallback all'ID 56
        window.open('<?php echo base_url(); ?>/pdfgen.php?id_print=' + printId + '&id_record=' + tokenId, '_blank');
    } catch (e) {
        // In caso di errore, usa l'ID di fallback
        window.open('<?php echo base_url(); ?>/pdfgen.php?id_print=' + printId + '&id_record=' + tokenId, '_blank');
    }
}

function editToken(tokenId) {
    // Chiudi il modal corrente
    $('#configurazione-otp-modal').modal('hide');
    
    // Apri il record del token nel modulo "Accesso con Token/OTP" in una nuova finestra
    // Recupera l'ID del modulo Token OTP
    $.get('<?php echo base_url(); ?>/ajax.php', {
        op: 'get_module_id',
        module_name: 'Accesso con Token/OTP'
    }, function(response) {
        if (response && response.id) {
            window.open('<?php echo base_url(); ?>/controller.php?id_module=' + response.id + '&id_record=' + tokenId, '_blank');
        } else {
            // Fallback: prova con il nome del modulo direttamente
            window.open('<?php echo base_url(); ?>/controller.php?id_module=<?php echo $dbo->fetchOne('SELECT id FROM zz_modules WHERE name = ?', ['Accesso con Token/OTP'])['id'] ?? ''; ?>&id_record=' + tokenId, '_blank');
        }
    }).fail(function() {
        // Fallback in caso di errore AJAX
        window.open('<?php echo base_url(); ?>/controller.php?id_module=<?php echo $dbo->fetchOne('SELECT id FROM zz_modules WHERE name = ?', ['Accesso con Token/OTP'])['id'] ?? ''; ?>&id_record=' + tokenId, '_blank');
    });
}

// Handle access type change
$(document).on('change', 'select[name="tipo_accesso"]', function() {
    if ($(this).val() === 'otp') {
        $('#email-field').show();
        $('input[name="email"]').attr('required', true);
    } else {
        $('#email-field').hide();
        $('input[name="email"]').removeAttr('required');
    }
});

// Gestione click pulsante "Chiudi" - ritorna allo step 1
$('#close-btn').on('click', function() {
    // Reset allo step 1
    window.currentStep = 1;
    
    // Nascondi il risultato del token e mostra il wizard
    $('#token-result').hide();
    $('#wizard-content').show();
});

// Initialize wizard on modal show
$(document).ready(function() {
    updateProgress();
    init();
});
</script>