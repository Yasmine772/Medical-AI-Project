import 'dart:convert';
import 'package:http/http.dart' as http;

Future<List<String>> listCloudflareModels({
  required String accountId,
  required String apiKey,
}) async {
  final uri = Uri.parse(
    'https://api.cloudflare.com/client/v4/accounts/$accountId/ai/models/search?per_page=50',
  );

  final response = await http.get(
    uri,
    headers: {'Authorization': 'Bearer $apiKey'},
  );

  if (response.statusCode != 200) {
    throw Exception('Cloudflare API error: ${response.statusCode}');
  }

  final data = jsonDecode(response.body) as Map<String, dynamic>;
  final models = (data['result'] as List?) ?? [];

  return models
      .where((m) {
        final name = (m['name'] as String?) ?? '';
        return name.contains('instruct') && !name.contains('vision');
      })
      .map((m) => m['name'] as String)
      .toList();
}