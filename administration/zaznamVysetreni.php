<?php
require_once "../header.php";
?>
<style>
    h3 {
        margin-top: 0px;
        color: white;
    }
    label {
        margin-bottom: 0px !important;
    }
    .row.rowunderline > div {
        border-bottom: 1px solid #cccccc;
    }
    .well {
        background-color: rgb(130,187,37);
        font-weight: normal;
    }
</style>

<div class="container">
    <form method="post">
        <div class="row">
            <div class="col-xs-12">
                <h2 class="text-center"><?= $titlePart ?></h2>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="well">
                    <div class="row">
                        <div class="col-xs-12">
                            <h3>Hlava</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>Předsun hlavy:</label>
                        </div>
                        <div class="col-xs-2">
                             <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_predsun[]" value="1"> &nbsp;
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>Rotace hlavy:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_rotace[]" value="vlevo">
                                vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_rotace[]" value="vpravo">
                                vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>Úklon hlavy:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_uklon[]" value="vlevo">
                                vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_uklon[]" value="vpravo">
                                vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>M. masseter - hypertonus:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_m_masseter_hypertonus[]" value="vlevo"> vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_m_masseter_hypertonus[]" value="vpravo"> vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>M. temporalis - hypertonus:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_m_temporalis_hypertonus[]" value="vlevo"> vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_m_temporalis_hypertonus[]" value="vpravo"> vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>M. pterygoideus - hypertonus:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_m_pterygoideus_hypertonus[]" value="vlevo"> vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_m_pterygoideus_hypertonus[]" value="vpravo"> vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>Palp. citlivost na processus mastoideus:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_processus_mastoideus[]" value="vlevo"> vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_processus_mastoideus[]" value="vpravo"> vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>Palp. citlivost na linea occipitalis:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_linea_occipitalis[]" value="vlevo"> vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="hlava_linea_occipitalis[]" value="vpravo"> vpravo
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="well">
                    <div class="row">
                        <div class="col-xs-12">
                            <h3>Krk</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>M. SCM - hypertonus:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="krk_m_SCM_hypertonus[]" value="vlevo"> vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="krk_m_SCM_hypertonus[]" value="vpravo"> vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>M. scalenus - hypertonus:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="krk_m_scalenus_hypertonus[]" value="vlevo"> vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="krk_m_scalenus_hypertonus[]" value="vpravo"> vpravo
                            </label>
                        </div>
                    </div>
                </div>
                <div class="well">
                    <div class="row">
                        <div class="col-xs-12">
                            <h3>Ramena</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>Výška ramen:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="ramena_vyska[]" value="leve_vyse">
                                L výše
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="ramena_vyska[]" value="prave_vyse">
                                P výše
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>Vnitřní rotace:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="ramena_vnitrni_rotace[]" value="vlevo"> vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="ramena_vnitrni_rotace[]" value="vpravo"> vpravo
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <div class="well">
                    <div class="row">
                        <div class="col-xs-12">
                            <h3>Trup</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Erectores spinae (krční) - hypertonus:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_erectores_spinae_krcni_hypertonus[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_erectores_spinae_krcni_hypertonus[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Erectores spinae (hrudní) - hypertonus:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_erectores_spinae_hrudni_hypertonus[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_erectores_spinae_hrudni_hypertonus[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Erectores spinae (bederní) - hypertonus:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_erectores_spinae_bederni_hypertonus[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_erectores_spinae_bederni_hypertonus[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>M. rectus abdominus - hypertonus:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_m_rectus_abdominus_hypertonus[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_m_rectus_abdominus_hypertonus[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>M. pectoralis minor - hypertonus:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_m_pectoralis_minor_hypertonus[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_m_pectoralis_minor_hypertonus[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>M. pectoralis major - hypertonus:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_m_pectoralis_major_hypertonus[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_m_pectoralis_major_hypertonus[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Hypotonie svalů:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_hypotonie_svalu[]" value="HSS">
                                        HSS
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Taile - více vykrojené:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_taile_vice_vykrojene[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_taile_vice_vykrojene[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Taile - méně vykrojené:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_taile_mene_vykrojene[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_taile_mene_vykrojene[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Hrudní kyfóza:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_hrudni_kyfoza[]" value="vyhlazena">
                                        vyhlazená
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_hrudni_kyfoza[]" value="hyperkyfoza">
                                        hyperkyfóza
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Bederní lordóza:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_bederni_lordoza[]" value="vyhlazena">
                                        vyhlazená
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="trup_bederni_lordoza[]" value="hyperlordoza">
                                        hyperlordóza
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="row">
                    <div class="col-xs-12">
                        <div class="well">
                            <div class="row">
                                <div class="col-xs-12">
                                    <h3>Horní končetiny</h3>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>M. biceps brachii - hypertonus:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="horni_koncetiny_m_biceps_brachii_hypertonus[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="horni_koncetiny_m_biceps_brachii_hypertonus[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>M. triceps brachii - hypertonus:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="horni_koncetiny_m_triceps_brachii_hypertonus[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="horni_koncetiny_m_triceps_brachii_hypertonus[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Extenzory prstů - hypertonus:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="horni_koncetiny_extenzory_prstu_hypertonus[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="horni_koncetiny_extenzory_prstu_hypertonus[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Flexory prstů - hypertonus:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="horni_koncetiny_flexory_prstu_hypertonus[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="horni_koncetiny_flexory_prstu_hypertonus[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Palp. citl. na epicondylitis lateralis humeri:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="horni_koncetiny_epicondylitis_lateralis_humeri[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="horni_koncetiny_epicondylitis_lateralis_humeri[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Palp. citl. na epicondylitis medialis humeri:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="horni_koncetiny_epicondylitis_medialis_humeri[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="horni_koncetiny_epicondylitis_medialis_humeri[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <div class="well">
                            <div class="row">
                                <div class="col-xs-12">
                                    <h3>Pánev</h3>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Sklon:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="panev_sklon[]" value="anteverze">
                                        anteverze
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="panev_sklon[]" value="retroverze">
                                        retroverze
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Sešikmení:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="panev_sesikmeni[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="panev_sesikmeni[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-xs-6">
                                    <label>Torze:</label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="panev_torze[]" value="vlevo">
                                        vlevo
                                    </label>
                                </div>
                                <div class="col-xs-2">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="panev_torze[]" value="vpravo">
                                        vpravo
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="well" style="min-height: 352px;">
                    <div class="row">
                        <div class="col-xs-12">
                            <h3>Dolní končetiny</h3>
                        </div>
                    </div>
                    <div class="row rowunderline">
                        <div class="col-xs-6">
                            <label>M. rectus femoris - hypertonus:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_m_rectus_femoris_hypertonus[]" value="vlevo">
                                vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_m_rectus_femoris_hypertonus[]" value="vpravo">
                                vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row rowunderline">
                        <div class="col-xs-6">
                            <label>M. biceps femoris - hypertonus:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_m_biceps_femoris_hypertonus[]" value="vlevo">
                                vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_m_biceps_femoris_hypertonus[]" value="vpravo">
                                vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row rowunderline">
                        <div class="col-xs-6">
                            <label>M. triceps surae - hypertonus:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_m_triceps_surae_hypertonus[]" value="vlevo">
                                vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_m_triceps_surae_hypertonus[]" value="vpravo">
                                vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row rowunderline">
                        <div class="col-xs-6">
                            <label>M. iliopsoas - hypertonus:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_m_iliopsoas_hypertonus[]" value="vlevo">
                                vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_m_iliopsoas_hypertonus[]" value="vpravo">
                                vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row rowunderline">
                        <div class="col-xs-6">
                            <label>M. TFL - hypertonus:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_m_TFL_hypertonus[]" value="vlevo">
                                vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_m_TFL_hypertonus[]" value="vpravo">
                                vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row rowunderline">
                        <div class="col-xs-6">
                            <label>Levá končetina - vytočení:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_leva_vytoceni[]" value="zevne">
                                zevně
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_leva_vytoceni[]" value="dovnitr">
                                dovnitř
                            </label>
                        </div>
                    </div>
                    <div class="row rowunderline">
                        <div class="col-xs-6">
                            <label>Pravá končetina - vytočení:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_prava_vytoceni[]" value="zevne">
                                zevně
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_prava_vytoceni[]" value="dovnitr">
                                dovnitř
                            </label>
                        </div>
                    </div>
                    <div class="row rowunderline">
                        <div class="col-xs-6">
                            <label>Zkrat:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_zkrat[]" value="vlevo">
                                vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_zkrat[]" value="vpravo">
                                vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row rowunderline">
                        <div class="col-xs-6">
                            <label>Blok articulatio tibiofibularis proximalis:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_articulatio_tibiofibularis_proximalis[]" value="vlevo">
                                vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_articulatio_tibiofibularis_proximalis[]" value="vpravo">
                                vpravo
                            </label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <label>Blok articulatio tibiofibularis distalis:</label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_articulatio_tibiofibularis_distalis[]" value="vlevo">
                                vlevo
                            </label>
                        </div>
                        <div class="col-xs-2">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="dolni_koncetiny_articulatio_tibiofibularis_distalis[]" value="vpravo">
                                vpravo
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <textarea class="form-control" rows="3" name="poznamka"></textarea>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-lg-12 text-center">
                <button class="btn btn-lg btn-success" name="submit">Uložit</button>
            </div>
        </div>
    </form>
</div>
