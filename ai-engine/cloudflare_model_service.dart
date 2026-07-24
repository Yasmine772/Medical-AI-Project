import 'dart:convert';
import 'package:http/http.dart' as http;

class CloudflareModelService {
  final String accountId;
  final String apiKey;
  String _currentModel;
  List<String> _availableModels = [];

  CloudflareModelService({
    required this.accountId,
    required this.apiKey,
    String defaultModel = '@cf/meta/llama-3.1-8b-instruct-fp8',
  }) : _currentModel = defaultModel;

  String get currentModel => _currentModel;
  List<String> get availableModels => _availableModels;

  Future<List<String>> fetchAvailableModels() async {
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

    _availableModels = models
        .where((m) {
          final name = (m['name'] as String?) ?? '';
          return name.contains('instruct') && !name.contains('vision');
        })
        .map((m) => m['name'] as String)
        .toList();

    return _availableModels;
  }

  bool switchModel(String model) {
    if (_availableModels.contains(model) || model == _currentModel) {
      _currentModel = model;
      return true;
    }
    return false;
  }
}