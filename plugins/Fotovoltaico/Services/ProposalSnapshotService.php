<?php

namespace Fotovoltaico\Services;

use Fotovoltaico\Models\Kit_items_model;
use Fotovoltaico\Models\Kits_model;
use Fotovoltaico\Models\Proposal_snapshots_model;
use Fotovoltaico\Models\Proposals_model;
use Fotovoltaico\Models\Proposal_versions_model;
use Fotovoltaico\Models\Tariffs_model;
use App\Models\Clients_model;
use App\Models\Users_model;

class ProposalSnapshotService
{
    private $Proposals_model;
    private $Proposal_versions_model;
    private $Proposal_snapshots_model;
    private $Clients_model;
    private $Users_model;
    private $Kits_model;
    private $Kit_items_model;
    private $Tariffs_model;

    public function __construct()
    {
        $this->Proposals_model = model(Proposals_model::class);
        $this->Proposal_versions_model = model(Proposal_versions_model::class);
        $this->Proposal_snapshots_model = model(Proposal_snapshots_model::class);
        $this->Clients_model = model(Clients_model::class);
        $this->Users_model = model(Users_model::class);
        $this->Kits_model = model(Kits_model::class);
        $this->Kit_items_model = model(Kit_items_model::class);
        $this->Tariffs_model = model(Tariffs_model::class);
    }

    public function build_snapshot($proposal, $version_number = null, $context = array())
    {
        if (!$proposal || !$proposal->id) {
            return array('success' => false, 'message' => 'Proposal not found');
        }

        $wizard_data = $this->_decode_json($proposal->wizard_data_json ?? '');
        $metadata = $this->_decode_json($proposal->metadata_json ?? '');
        $version = $version_number ?: (int) ($proposal->current_version ?? 0);
        $client = $this->_resolve_client($proposal);
        $contact = $this->_resolve_contact($proposal);
        $lead = $this->_resolve_lead($proposal);
        $kit = $this->_resolve_kit($wizard_data);
        $tariff = $this->_resolve_tariff($wizard_data, $proposal);
        $insolation = $this->_build_insolation_block($wizard_data, $context);
        $technical = $this->_decode_json(get_array_value($wizard_data, 'technical_calc') ?: get_array_value($context, 'technical_calc'));
        $finance = $this->_decode_json(get_array_value($wizard_data, 'finance_calc') ?: get_array_value($context, 'finance_calc'));
        $commercial = $this->_build_commercial_block($proposal, $wizard_data, $kit, $tariff);
        $results = array(
            'technical' => $technical,
            'finance' => $finance,
            'commercial' => $commercial,
        );

        $snapshot = array(
            'proposal' => $this->_build_proposal_block($proposal, $version),
            'version' => array(
                'number' => (int) $version,
                'status' => $proposal->status ?: 'draft',
                'generated_at' => get_my_local_time(),
                'source_step' => get_array_value($context, 'step') ?: get_array_value($wizard_data, 'wizard_step') ?: '',
            ),
            'client' => $client,
            'consumidor' => $this->_build_consumption_block($proposal, $wizard_data),
            'unit_consuming' => $proposal->consumer_unit ?: '',
            'crm' => array(
                'client' => $client,
                'lead' => $lead,
                'contact' => $contact,
            ),
            'kit' => $kit,
            'tariff' => $tariff,
            'insolation' => $insolation,
            'financial' => $finance,
            'technical' => $technical,
            'commercial' => $commercial,
            'wizard' => $wizard_data,
            'metadata' => $metadata,
            'results' => $results,
        );

        $snapshot['integrity_hash'] = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return array(
            'success' => true,
            'snapshot' => $snapshot,
            'snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'snapshot_hash' => $snapshot['integrity_hash'],
        );
    }

    public function store_snapshot($proposal, $proposal_version_id, $created_by = 0, $context = array())
    {
        $snapshot_result = $this->build_snapshot($proposal, (int) ($proposal->current_version ?? 0), $context);
        if (!get_array_value($snapshot_result, 'success')) {
            return false;
        }

        return $this->store_snapshot_json($proposal, $proposal_version_id, get_array_value($snapshot_result, 'snapshot_json'), $created_by);
    }

    public function store_snapshot_json($proposal, $proposal_version_id, $snapshot_json, $created_by = 0)
    {
        $snapshot_json = is_string($snapshot_json) ? $snapshot_json : json_encode($snapshot_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data = array(
            'proposal_id' => (int) $proposal->id,
            'proposal_version_id' => (int) $proposal_version_id,
            'snapshot_json' => $snapshot_json,
            'snapshot_hash' => hash('sha256', $snapshot_json),
            'created_by' => (int) $created_by,
            'created_at' => get_my_local_time(),
            'updated_at' => get_my_local_time(),
        );

        return $this->Proposal_snapshots_model->ci_save($data);
    }

    public function get_latest_snapshot($proposal_id)
    {
        return $this->Proposal_snapshots_model->get_latest_snapshot($proposal_id);
    }

    public function get_snapshot_for_pdf($proposal_id, $proposal_version_id = 0)
    {
        if ($proposal_version_id) {
            $snapshot = $this->Proposal_snapshots_model->get_snapshot_by_version($proposal_version_id);
            if ($snapshot) {
                return $snapshot;
            }
        }

        return $this->Proposal_snapshots_model->get_latest_snapshot($proposal_id);
    }

    private function _build_proposal_block($proposal, $version)
    {
        return array(
            'id' => (int) $proposal->id,
            'proposal_code' => $proposal->proposal_code ?: '',
            'title' => $proposal->title ?: '',
            'status' => $proposal->status ?: 'draft',
            'version_number' => (int) $version,
            'current_version' => (int) ($proposal->current_version ?? 0),
            'currency' => $proposal->currency ?: get_setting('default_currency'),
            'subtotal' => (float) ($proposal->subtotal ?? 0),
            'discount_total' => (float) ($proposal->discount_total ?? 0),
            'tax_total' => (float) ($proposal->tax_total ?? 0),
            'total' => (float) ($proposal->total ?? 0),
            'issue_date' => $proposal->issue_date ?: '',
            'valid_until' => $proposal->valid_until ?: '',
            'created_at' => $proposal->created_at ?: '',
            'updated_at' => $proposal->updated_at ?: '',
        );
    }

    private function _build_consumption_block($proposal, $wizard_data)
    {
        return array(
            'consumer_unit' => $proposal->consumer_unit ?: '',
            'consumption_avg' => (float) ($proposal->consumption_avg ?? 0),
            'monthly_bill_value' => (float) get_array_value($wizard_data, 'monthly_bill_value'),
            'consumption_profile' => get_array_value($wizard_data, 'consumption_profile') ?: '',
            'notes' => $proposal->notes ?: '',
        );
    }

    private function _resolve_client($proposal)
    {
        if ((int) ($proposal->client_id ?? 0)) {
            $client = $this->Clients_model->get_one($proposal->client_id);
            if ($client && $client->id) {
                return array(
                    'id' => (int) $client->id,
                    'company_name' => $client->company_name,
                    'is_lead' => (int) ($client->is_lead ?? 0),
                );
            }
        }

        return array();
    }

    private function _resolve_lead($proposal)
    {
        if ((int) ($proposal->lead_id ?? 0)) {
            $lead = $this->Clients_model->get_one($proposal->lead_id);
            if ($lead && $lead->id) {
                return array(
                    'id' => (int) $lead->id,
                    'company_name' => $lead->company_name,
                    'is_lead' => (int) ($lead->is_lead ?? 1),
                );
            }
        }

        return array();
    }

    private function _resolve_contact($proposal)
    {
        if ((int) ($proposal->contact_id ?? 0)) {
            $contact = $this->Users_model->get_one($proposal->contact_id);
            if ($contact && $contact->id) {
                return array(
                    'id' => (int) $contact->id,
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'company_name' => $contact->company_name,
                    'client_id' => (int) ($contact->client_id ?? 0),
                );
            }
        }

        return array();
    }

    private function _resolve_kit($wizard_data)
    {
        $kit_id = (int) get_array_value($wizard_data, 'kit_id');
        if (!$kit_id) {
            return array();
        }

        $kit = $this->Kits_model->get_kit_with_items($kit_id);
        if (!$kit) {
            return array();
        }

        $items = array();
        foreach ((array) ($kit->items ?? array()) as $item) {
            $items[] = array(
                'id' => (int) ($item->id ?? 0),
                'product_id' => (int) ($item->product_id ?? 0),
                'product_title' => $item->product_title ?? '',
                'product_type' => $item->product_type ?? '',
                'sku' => $item->sku ?? '',
                'quantity' => (float) ($item->quantity ?? 0),
                'unit_price' => (float) ($item->unit_price ?? 0),
                'unit_cost' => (float) ($item->unit_cost ?? 0),
                'total_price' => (float) ($item->total_price ?? 0),
                'total_cost' => (float) ($item->total_cost ?? 0),
                'notes' => $item->notes ?? '',
            );
        }

        return array(
            'id' => (int) $kit->id,
            'title' => $kit->title ?: '',
            'code' => $kit->code ?: '',
            'power_kwp' => (float) ($kit->power_kwp ?? 0),
            'status' => $kit->status ?: '',
            'notes' => $kit->notes ?: '',
            'total_cost' => (float) ($kit->total_cost ?? 0),
            'total_price' => (float) ($kit->total_price ?? 0),
            'margin_value' => (float) ($kit->margin_value ?? 0),
            'margin_percent' => (float) ($kit->margin_percent ?? 0),
            'items' => $items,
        );
    }

    private function _resolve_tariff($wizard_data, $proposal)
    {
        $tariff_id = (int) get_array_value($wizard_data, 'tariff_id');
        if (!$tariff_id && (int) ($proposal->distributor_id ?? 0)) {
            $tariff = $this->Tariffs_model->get_current_tariff((int) $proposal->distributor_id);
            if ($tariff) {
                $tariff_id = (int) $tariff->id;
            }
        }

        if (!$tariff_id) {
            return array();
        }

        $tariff = $this->Tariffs_model->get_details(array('id' => $tariff_id))->getRow();
        if (!$tariff) {
            return array();
        }

        return array(
            'id' => (int) $tariff->id,
            'distributor_id' => (int) ($tariff->distributor_id ?? 0),
            'distributor_title' => $tariff->distributor_title ?? '',
            'modality' => $tariff->modality ?? '',
            'subgroup' => $tariff->subgroup ?? '',
            'te' => (float) ($tariff->te ?? 0),
            'tusd' => (float) ($tariff->tusd ?? 0),
            'flag_name' => $tariff->flag_name ?? '',
            'flag_value' => (float) ($tariff->flag_value ?? 0),
            'valid_from' => $tariff->valid_from ?? '',
            'valid_to' => $tariff->valid_to ?? '',
        );
    }

    private function _build_insolation_block($wizard_data, $context)
    {
        return array(
            'city' => get_array_value($wizard_data, 'insolation_city') ?: '',
            'state' => get_array_value($wizard_data, 'insolation_state') ?: '',
            'latitude' => get_array_value($wizard_data, 'latitude') ?: '',
            'longitude' => get_array_value($wizard_data, 'longitude') ?: '',
            'annual_insolation' => (float) get_array_value($wizard_data, 'annual_insolation'),
            'insolation_source' => get_array_value($wizard_data, 'insolation_source') ?: '',
            'cache_hit' => (int) get_array_value($context, 'cache_hit'),
            'provider' => get_array_value($context, 'provider') ?: '',
            'monthly_insolation' => get_array_value($wizard_data, 'monthly_insolation') ?: array(),
            'manual_override_applied' => (int) get_array_value($context, 'manual_override_applied'),
        );
    }

    private function _build_commercial_block($proposal, $wizard_data, $kit, $tariff)
    {
        return array(
            'title' => $proposal->title ?: '',
            'subtotal' => (float) ($proposal->subtotal ?? 0),
            'discount_total' => (float) ($proposal->discount_total ?? 0),
            'tax_total' => (float) ($proposal->tax_total ?? 0),
            'total' => (float) ($proposal->total ?? 0),
            'kit_total_cost' => (float) get_array_value($kit, 'total_cost'),
            'kit_total_price' => (float) get_array_value($kit, 'total_price'),
            'kit_margin_value' => (float) get_array_value($kit, 'margin_value'),
            'kit_margin_percent' => (float) get_array_value($kit, 'margin_percent'),
            'tariff_value' => (float) get_array_value($tariff, 'te') + (float) get_array_value($tariff, 'tusd') + (float) get_array_value($tariff, 'flag_value'),
            'entry_value' => (float) get_array_value($wizard_data, 'entry_value'),
            'entry_percent' => (float) get_array_value($wizard_data, 'entry_percent'),
        );
    }

    private function _decode_json($json_text)
    {
        if (is_array($json_text)) {
            return $json_text;
        }

        $json_text = trim((string) $json_text);
        if ($json_text === '') {
            return array();
        }

        $decoded = json_decode($json_text, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : array();
    }
}
