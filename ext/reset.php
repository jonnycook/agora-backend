<?php

require_once('includes/header.php');

$sql = "SET foreign_key_checks = 0; -- 0.000 s
TRUNCATE TABLE `changes`; -- 0.008 s
TRUNCATE TABLE `extension_errors`; -- 0.003 s
TRUNCATE TABLE `m_bundle_elements`; -- 0.003 s
TRUNCATE TABLE `m_bundles`; -- 0.002 s
TRUNCATE TABLE `m_competitive_list_elements`; -- 0.003 s
TRUNCATE TABLE `m_competitive_lists`; -- 0.002 s
TRUNCATE TABLE `m_root_elements`; -- 0.003 s
TRUNCATE TABLE `m_list_elements`; -- 0.003 s
TRUNCATE TABLE `m_lists`; -- 0.002 s
TRUNCATE TABLE `m_composite_slots`; -- 0.002 s
TRUNCATE TABLE `m_composites`; -- 0.003 s
TRUNCATE TABLE `m_decisions`; -- 0.003 s
TRUNCATE TABLE `m_decision_elements`; -- 0.003 s
TRUNCATE TABLE `m_collection_elements`; -- 0.003 s
TRUNCATE TABLE `m_products`; -- 0.003 s
TRUNCATE TABLE `m_session_elements`; -- 0.003 s
TRUNCATE TABLE `m_sessions`; -- 0.003 s
TRUNCATE TABLE `m_data`; -- 0.003 s
TRUNCATE TABLE `m_feelings`; -- 0.003 s
TRUNCATE TABLE `m_arguments`; -- 0.003 s
TRUNCATE TABLE `m_descriptors`; -- 0.003 s
TRUNCATE TABLE `update_errors`; -- 0.009 s
TRUNCATE TABLE `update_logs`; -- 0.002 s";

$queries = explode("\n", $sql);

foreach ($queries as $query) {
	mysqli_query($mysqli, $query) or die(mysqli_error($mysqli));
}