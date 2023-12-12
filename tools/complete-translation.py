# Use OpenAI to complete missing translations for a single translation file.
# You must set the OPENAI_API_KEY environment variable with a valid OpenAI API key.
# Usage:
#   python3 German_utf8 > ../translations/German_utf8.txt
# Then use the following to properly order/arrange the translation file:
#   perl update_translation.pl German_utf8
# Notes:
#  - This has only been tested on UTF-8.
#  - Translations are mostly correct, but some have extra quotes at the start
#    or end.  So you must proof-read the results.
#
# TODO: This does not yet properly handle the translations that are abbreviations
# in the English-US.txt file (e.g. "brief-description-help").
# In the short term, you can find these translations as comments in the translation
# file (before running the above perl command which will remove them.)
import sys
import os
import openai
import chardet


def load_translations(file_path):
    with open(file_path, 'rb') as file:
        raw_data = file.read()
        result = chardet.detect(raw_data)
        encoding = result['encoding']

    translations = {}
    with open(file_path, 'r', encoding=encoding) as file:
        for line in file:
            if line.startswith('#'):
                continue
            if line.strip() and ':' in line:
                key, value = line.split(':', 1)
                translations[key.strip()] = value.strip()
    return translations


def remove_surrounding_quotes(s):
    if s.startswith("'") and s.endswith("'"):
        # Remove the leading and trailing single quotes
        s = s[1:-1]
    if s.startswith('"') and s.endswith('"'):
        # Remove the leading and trailing single quotes
        s = s[1:-1]
    return s

def translate_batch(openai, batch, language, charset):
    # Constructing the prompt with clear instructions
    prompt_instructions = f"Translate the following lines of English text to {language} (charset: {charset}). Provide one line of translation for each line of English text:\n"
    prompt_lines = "\n".join([f"{i+1}. '{line}'" for i, line in enumerate(batch)])
    full_prompt = prompt_instructions + prompt_lines

    try:
        response = openai.Completion.create(
            engine="text-davinci-003",
            prompt=full_prompt,
            max_tokens=60 * len(batch)  # Adjust max_tokens based on batch size
        )
        # Splitting the response into individual translations
        translations = response.choices[0].text.strip().split('\n')
        print("# Translations:", translations)

        # Matching translations to their corresponding lines
        matched_translations = [t.split('. ', 1)[-1] for t in translations if t]
        # Cleaning up quotes from the results
        cleaned_translations = [remove_surrounding_quotes(t) for t in matched_translations]
        return cleaned_translations
    except Exception as e:
        print(f"# Error during batch translation: {e}")
        return [None] * len(batch)

def main():
    if len(sys.argv) != 2:
        print("Usage: python3 complete-translation.py [Language]")
        sys.exit(1)

    language = sys.argv[1]
    base_path = '../translations'
    english_file = os.path.join(base_path, 'English-US.txt')
    translation_file = os.path.join(base_path, f'{language}.txt')

    if not os.path.exists(english_file) or not os.path.exists(translation_file):
        print("Error: Translation files not found.")
        sys.exit(1)

    english_translations = load_translations(english_file)
    target_translations = load_translations(translation_file)

    charset = target_translations.get('charset', 'utf-8')
    api_key = os.getenv('OPENAI_API_KEY')

    if not api_key:
        print("Error: OpenAI API key not set.")
        sys.exit(1)

    openai.api_key = api_key

    missing_translations = [value for key, value in english_translations.items() if key not in target_translations]
    batch_size = 5  # Adjust the batch size as needed
    for i in range(0, len(missing_translations), batch_size):
        batch = missing_translations[i:i + batch_size]
        print("# Translating batch:", batch)
        # Print status update to stderr
        sys.stderr.write(f"Translating batch {i // batch_size + 1}/{len(missing_translations) // batch_size + 1}\n")
        translations = translate_batch(openai, batch, language, charset)
        for original, translated in zip(batch, translations):
            if translated:
                print(f"{original}: {translated}")

if __name__ == "__main__":
    main()

