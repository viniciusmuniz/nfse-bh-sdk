<?php

namespace NFse\Service;

use Exception;
use Mpdf\Mpdf;
use NFse\Helpers\Utils;
use NFse\Models\NFse;

class PrintPDFNFse
{
    private $html;
    private $nfse;
    private $logo64;
    private $logoPrefeitura;
    private $textPrefeitura;
    private $logoNota10;

    /**
     * recebe o objeto da nota fiscal para impressão.
     *
     * @param NFse\Models\NFse;
     */
    public function __construct(NFse $nfse, string $logo64, string $logoPrefeitura, $textPrefeitura, $logoNota10)
    {
        $this->nfse = $nfse;
        $this->logo64 = $logo64;
        $this->logoPrefeitura = $logoPrefeitura;
        $this->textPrefeitura = $textPrefeitura;
        $this->logoNota10 = $logoNota10;
    }

    //gera e retorna o pdf da nota
    // I - HTML
    // D - Dowload do PDF
    // P - IMPRIMIR

    public function getPDF($type)
    {
        if ($type == 'I') {
            echo $this->getPrintable('I');
        } else {
            try {
                $mPDF = new Mpdf();
                $mPDF->SetDefaultFont('chelvetica');
                $html = $this->getPrintable($type);
                if ($this->nfse->cancellationCode) {
                    $mPDF->SetWatermarkText('NFS-e Cancelada');
                    $mPDF->showWatermarkText = true;
                }
                $mPDF->WriteHTML($html);

                return $mPDF->Output('NFse.pdf', $type);
            } catch (Exception $e) {
                throw $e;
            }
        }
    }

    //seta os dados e retorna o html da nota
    private function getPrintable($type)
    {
        $this->html = file_get_contents(__DIR__ . '/../../storage/' . 'cdn' . \DIRECTORY_SEPARATOR . 'html' . \DIRECTORY_SEPARATOR . 'print.html');

        $operations = [
            1 => 'Tributação no município',
            2 => 'Tributação fora do município',
            3 => 'Isenção',
            4 => 'Imune',
            5 => 'Exigibilidade suspensa por decisão judicial',
            6 => 'Exigibilidade suspensa por procedimento administrativo',
        ];

        $regimes = [
            1 => 'Microempresa municipal',
            2 => 'Estimativa',
            3 => 'Sociedade de profissionais',
            4 => 'Cooperativa',
            5 => 'MEI – Simples Nacional',
            6 => 'ME ou EPP do Simples Nacional',
        ];

        if ($type == 'I') {
            $printCss = '@media print {
                body {
                    font: 19px "Trebuchet MS", Verdana, Arial;
                    color: #175366;
                    text-align: center;
                }
                .logo {
                    max-width: 230px;
                    padding: 10px;
                }
                .teste {
                    font: 19px "Trebuchet MS", Verdana, Arial;
                    color: #175366;
                }
                .hh1 {
                    font: 25px Verdana, Arial;
                }
                .hh2 {
                    font: bold 19px "Trebuchet MS", Verdana, Arial;
                }
                .hh3 {
                    font: 19px "Trebuchet MS", Verdana, Arial;
                }
                .noprint {
                    display: none;
                }
                .box01 {
                    background: none;
                }
                .box02 {
                    background: none;
                }
                .box03 {
                    background: none;
                }
                .box04 {
                    background: none;
                }
                .box05 {
                    background: none;
                }
                h1 {
                    font-size: 19px;
                }
                h2 {
                    font-size: 19px;
                }
                h3 {
                    font-size: 19px;
                }
                .numeroDestaque {
                    font-size: 30px;
                }
                .valorLiquido {
                    font-size: 20px;
                    color: #c32b16;
                    padding: 5px 5px 2px;
                }
                .issRetido {
                    font-size: 20px;
                    color: #c32b16;
                    padding: 5px 5px 2px;
                }
                .cnpjPrincipal {
                    font-size: 19px;
                    font-weight: bold;
                }
                .subTitulo {
                    font-size: 19px;
                    font-weight: bold;
                }
                .tableTributos {
                    font-size: 19px;
                }
                .tableTributos th {
                    font-size: 19px;
                    background: #eeeeee;
                    text-align: center;
                    padding: 1px 3px;
                }
                .tableTributos td {
                    font-size: 19px;
                    background: #FFFFFF;
                    text-align: right;
                    padding: 1px 3px;
                }
                .dataEmissao {
                    font-size: 19px;
                    font-weight: bold;
                }
                .title {
                    font-size: 25px;
                }
                .linhaDivisao {
                    display: none;
                }
                .servicos {
                    font-size: 19px;
                }
            }';
        } else {
            $printCss = '@media print {
                body {
                    font: 10px "Trebuchet MS", Verdana, Arial;
                    color: #175366;
                    text-align: center;
                }
                .noprint {
                    display: none;
                }
                .box01, .box02, .box03, .box04, .box05 {
                    background: none;
                }
                .linhaDivisao {
                    display: block;
                    margin-bottom: -1px;
                }
                .hh2{
                    font: bold 13px "Trebuchet MS", Verdana, Arial;
                    color: #175366;
                    border-bottom: 1px solid #65A0C0;
                    margin: 0px;
                }
                .servicos {
                    padding: 0 2px;
                    font-size: 9px;
                }
                .subTitulo {
                    font-size: 11px;
                    font-weight: bold;
                }
            }';
        }

        if ($this->nfse->cancellationCode) {
            $printCss .= '
                #container:before{
                  content: \'NFS-e Cancelada\';
                  position: absolute;
                  top: 0;
                  bottom: 0;
                  left: 0;
                  right: 0;
                  z-index: 1;
                  color: black;
                  font-size: 100px;
                  font-weight: 500px;
                  display: grid;
                  justify-content: center;
                  align-content: center;
                  opacity: 0.2;
                  transform: rotate(-45deg);
                  white-space: nowrap;
                }
            ';
        }

        $nfseNumberReplaced = '';

        if (!empty($this->nfse->nfseNumberReplaced)) {
            $nfseNumberReplaced = '
            <tr>
                <td colspan="2">
                    <hr class="linhaDivisao"/>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                   <div class="box04">
                        <table>
                            <tbody>
                                <tr>
                                    <td colspan="2">
                                        <span>
                                            NFS-e Substituída:' . substr($this->nfse->nfseNumberReplaced, 0, 4) . '/' . substr($this->nfse->nfseNumberReplaced, 4) .
                '</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </td>
            </tr>';
        }

        $specialTaxRegime = '';

        if (!empty($this->nfse->service->specialTaxRegime)) {
            $specialTaxRegime = '

            <div style="margin: 0px 5px;">
                <span id="j_id106"> </span>
                <table border="0" cellpadding="4" cellspacing="0" width="100%">
                    <tbody>
                        <tr>
                            <span id="form:j_id126">
                                <td width="33%" height="25" align="left" valign="middle" class="bordaLateral">
                                    <p class="teste">
                                        <span class="subTitulo"> Regime Especial de Tributa&ccedil;&atilde;o: </span>
                                        ' . $regimes[$this->nfse->service->specialTaxRegime] . '
                                    </p>
                                </td>
                            </span>
                        </tr>
                    </tbody>
                </table>
            </div>
            ';
        }

        $optanteSimplesNacional = '';

        if ($this->nfse->service->simpleNational) {
            $optanteSimplesNacional = '
           <span id="form:j_id177">
                <tr>
                    <td class="bordaInferior" style="padding: 5px;">
                        <span class="subTitulo">Documento emitido por ME ou EPP optante pelo Simples Nacional. N&atilde;o gera direito a credito fiscal de IPI.</span>
                    </td>
                </tr>
            </span>';
        }

        $this->html = str_replace(
            [
                //css
                '/* {PRINT_CSS}*/',
                //header
                '{ANO}',
                '{NFSE_NUMERO}',
                '{DATA_EMISSAO}',
                '{HORA_EMISSAO}',
                '{COMPETENCIA}',
                '{CODIGO_VERIFICACAO}',
                '{LOGO_BASE_64}',
                '{NFE_SUBSTITUIDA}',
                //presatador
                '{RAZAO_SOCIAL_PRESTADOR}',
                '{CPF_CNPJ_PRESTADOR}',
                '{INSCRICAO_MUNICIPAL_PRESTADOR}',
                '{LOGRADOURO_PRESTADOR}',
                '{NUMERO_ENDERECO_PRESTADOR}',
                '{BAIRRO_PRESTADOR}',
                '{CEP_PRESTADOR}',
                '{MUNICIPIO_PRESTADOR}',
                '{ESTADO_PRESTADOR}',
                '{TELEFONE_PRESTADOR}',
                '{EMAIL_PRESTADOR}',
                //Tomador
                '{RAZAO_SOCIAL_TOMADOR}',
                '{CPF_CNPJ_TOMADOR}',
                '{INSCRICAO_MUNICIPAL_TOMADOR}',
                '{LOGRADOURO_TOMADOR}',
                '{NUMERO_ENDERECO_TOMADOR}',
                '{BAIRRO_TOMADOR}',
                '{CEP_TOMADOR}',
                '{MUNICIPIO_TOMADOR}',
                '{ESTADO_TOMADOR}',
                '{TELEFONE_TOMADOR}',
                '{EMAIL_TOMADOR}',
                //body
                '{DESCRIMINACAO}',
                '{CODIGO_TRIBUTACAO_MUNICIPAL}',
                '{DESCRICAO_TRIBUTACAO_MUNICIPAL}',
                '{ITEM_LISTA_SERVICO}',
                '{DESCRICAO_LISTA_SERVICO}',
                '{CODIGO_MUNICIPIO_GERADOR}',
                '{NOME_MUNICIPIO_GERADOR}',
                '{NATUREZA_OPERACAO}',
                '{REGIME_ESPECIAL_TRIBUTACAO}',
                //valores
                '{VALOR_SERVICOS}',
                '{VALOR_DESCONTO_CONDICIONADO}',
                '{TOTAL_RETENCOES_FEDERAIS}',
                '{VALOR_ISS_RETIDO}',
                '{VALOR_LIQUIDO}',
                '{DEDUCOES}',
                '{VALOR_DESCONTO_INCONDICIONADO}',
                '{BASE_CALCULO}',
                '{ALIQUOTA_SERVICOS}',
                '{VALOR_ISS}',
                '{VALOR_PIS}',
                '{VALOR_COFINS}',
                '{VALOR_IR}',
                '{VALOR_CSLL}',
                '{VALOR_INSS}',
                //footer
                '{OPITANTE_PELO_SIMPLES}',
                '{PREFEITURA_LOGO_BASE_64}',
                '{TEXT_PREFEITURA}',
                '{LOGO_NOTA_10}',
            ],
            [
                //css
                $printCss,
                //header
                $this->nfse->year,
                $this->nfse->number,
                $this->nfse->dateEmission,
                ' às ' . $this->nfse->timeEmission,
                $this->nfse->competence,
                $this->nfse->verificationCode,
                $this->logo64,
                $nfseNumberReplaced,

                //prestador
                $this->nfse->provider->name,
                Utils::mask((string) $this->nfse->provider->cnpj, '##.###.###/####-##'),
                Utils::mask((string) $this->nfse->provider->inscription, '#######/###-#'),
                //prestador endereço
                $this->nfse->provider->address->address,
                $this->nfse->provider->address->number,
                $this->nfse->provider->address->neighborhood,
                Utils::mask((string) $this->nfse->provider->address->zipCode, '##.###-###'),
                $this->nfse->provider->address->city,
                $this->nfse->provider->address->state,
                //dados do prestador
                Utils::addPhoneMask($this->nfse->provider->phone),
                $this->nfse->provider->email,

                //tomador
                $this->nfse->taker->name,
                (\strlen($this->nfse->taker->document) > 11) ? Utils::mask((string) $this->nfse->taker->document, '##.###.###/####-##') : Utils::mask((string) $this->nfse->taker->document, '###.###.###-##'),
                ($this->nfse->taker->municipalRegistration) ? Utils::mask((string) $this->nfse->taker->municipalRegistration, '#######/###-#') : 'Não Informado',

                //tomador endereço
                $this->nfse->taker->address,
                $this->nfse->taker->number,
                $this->nfse->taker->neighborhood,
                Utils::mask((string) $this->nfse->taker->zipCode, '##.###-###'),
                $this->nfse->taker->city,
                $this->nfse->taker->state,
                Utils::addPhoneMask($this->nfse->taker->phone),
                $this->nfse->taker->email,

                //body
                $this->nfse->service->description,
                Utils::mask((string) $this->nfse->service->municipalityTaxationCode, '####-#/##-##'),
                strtolower($this->nfse->service->taxCodeDescription),
                //item
                $this->nfse->service->itemList,
                strtolower($this->nfse->service->itemDescription),

                $this->nfse->service->municipalCode,
                $this->nfse->service->municipalName,

                $operations[$this->nfse->service->nature],
                $specialTaxRegime,

                //valores
                Utils::formatRealMoney($this->nfse->service->serviceValue ?? 0),
                Utils::formatRealMoney($this->nfse->service->discountCondition ?? 0),
                Utils::formatRealMoney($this->nfse->service->otherWithholdings ?? 0),
                Utils::formatRealMoney($this->nfse->service->issValueWithheld ?? 0),
                Utils::formatRealMoney($this->nfse->service->netValue ?? 0),
                Utils::formatRealMoney($this->nfse->service->valueDeductions ?? 0),
                Utils::formatRealMoney($this->nfse->service->unconditionedDiscount ?? 0),
                Utils::formatRealMoney($this->nfse->service->calculationBase ?? 0),
                $this->nfse->service->aliquot * 100 . ' % ',
                Utils::formatRealMoney($this->nfse->service->issValue ?? 0),
                Utils::formatRealMoney($this->nfse->service->valuePis ?? 0),
                Utils::formatRealMoney($this->nfse->service->valueConfis ?? 0),
                Utils::formatRealMoney($this->nfse->service->valueIR ?? 0),
                Utils::formatRealMoney($this->nfse->service->valueCSLL ?? 0),
                Utils::formatRealMoney($this->nfse->service->valueINSS ?? 0),
                $optanteSimplesNacional,
                $this->logoPrefeitura,
                $this->textPrefeitura,
                $this->logoNota10,
            ],
            $this->html
        );

        return $this->html;
    }
}
