"""Test openrouter/free with response_format"""
import os, json, time
from openai import OpenAI
c = OpenAI(base_url="https://openrouter.ai/api/v1", api_key=os.environ["OPENROUTER_KEY"])

# Test without response_format
for use_rf in [False, True]:
    kwargs = dict(model="openrouter/free", messages=[{"role": "user", "content": 'Return JSON: {"type": "question", "question": "test?", "options": ["a","b"]}'}], max_tokens=200, temperature=0.1)
    if use_rf:
        kwargs["response_format"] = {"type": "json_object"}
    
    try:
        r = c.chat.completions.create(**kwargs)
        content = r.choices[0].message.content or ""
        print(f"\nresponse_format={use_rf}:")
        print(f"  raw: {content[:150]}")
        try:
            json.loads(content)
            print(f"  VALID JSON")
        except:
            # try extraction
            s = content.find("{")
            e = content.rfind("}")
            if s>=0 and e>s:
                try:
                    json.loads(content[s:e+1])
                    print(f"  EXTRACTED JSON")
                except:
                    print(f"  NOT JSON")
            else:
                print(f"  NOT JSON")
    except Exception as ex:
        print(f"\nresponse_format={use_rf}: ERROR: {type(ex).__name__}: {str(ex)[:80]}")
    time.sleep(2)
