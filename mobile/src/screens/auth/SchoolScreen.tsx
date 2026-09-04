import React from 'react';
import { Pressable, StyleSheet, Text, View, TextInput } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { AuthButton, AuthHeader } from './AuthControls';

type SchoolScreenProps = {
  school: string;
  error: string;
  onChange: (value: string) => void;
  onBack: () => void;
  onContinue: () => void;
};

export function SchoolScreen({
  school,
  error,
  onChange,
  onBack,
  onContinue,
}: SchoolScreenProps) {
  return (
    <SafeAreaView style={styles.screen}>
      <AuthHeader onBack={onBack} />

      <View style={styles.container}>
        {/* Header Section */}
        <View style={styles.header}>
          <View style={styles.iconBadge}>
            <Ionicons name="school" size={32} color="#0B132B" />
          </View>
          <Text style={styles.title}>Find your school</Text>
          <Text style={styles.lead}>
            Enter the unique school code provided by your administration.
          </Text>
        </View>

        {/* Input Card */}
        <View style={styles.card}>
          <View style={styles.fieldContainer}>
            <Text style={styles.label}>School Code</Text>
            <View style={[styles.inputWrapper, !!error && styles.inputWrapperError]}>
              <Ionicons
                name="key-outline"
                size={20}
                color={error ? '#EF4444' : '#6C7A9C'}
                style={styles.fieldIcon}
              />
              <TextInput
                style={styles.input}
                value={school}
                onChangeText={onChange}
                placeholder="e.g. EDL-00001"
                placeholderTextColor="#A0AEC0"
                autoCapitalize="characters"
                autoCorrect={false}
                returnKeyType="next"
                onSubmitEditing={onContinue}
              />
              {school.length > 0 && (
                <Pressable onPress={() => onChange('')} hitSlop={8}>
                  <Ionicons name="close-circle" size={18} color="#A0AEC0" />
                </Pressable>
              )}
            </View>
            {!!error && (
              <View style={styles.errorRow}>
                <Ionicons name="alert-circle-outline" size={16} color="#EF4444" />
                <Text style={styles.errorText}>{error}</Text>
              </View>
            )}
          </View>

          <AuthButton label="Continue" onPress={onContinue} />
        </View>

        {/* Helper Note */}
        <View style={styles.helpBox}>
          <Ionicons name="information-circle-outline" size={18} color="#6C7A9C" />
          <Text style={styles.helpText}>
            Don't have a school code? Contact your school administrator or teacher.
          </Text>
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: '#F4F6FA',
  },
  container: {
    flex: 1,
    paddingHorizontal: 24,
    paddingTop: 16,
  },
  header: {
    alignItems: 'center',
    marginBottom: 28,
  },
  iconBadge: {
    width: 68,
    height: 68,
    borderRadius: 22,
    backgroundColor: '#FFD166',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 18,
    shadowColor: '#0B132B',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 3,
  },
  title: {
    fontFamily: 'Poppins_700Bold',
    fontSize: 26,
    lineHeight: 34,
    color: '#0B132B',
    textAlign: 'center',
  },
  lead: {
    fontFamily: 'Poppins_400Regular',
    fontSize: 14,
    lineHeight: 22,
    color: '#6C7A9C',
    textAlign: 'center',
    marginTop: 8,
    paddingHorizontal: 12,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 24,
    padding: 24,
    gap: 20,
    shadowColor: '#0B132B',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.05,
    shadowRadius: 18,
    elevation: 3,
  },
  fieldContainer: {
    gap: 8,
  },
  label: {
    fontFamily: 'Poppins_600SemiBold',
    fontSize: 13,
    color: '#0B132B',
    marginLeft: 2,
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    height: 54,
    backgroundColor: '#F8FAFC',
    borderRadius: 16,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    paddingHorizontal: 16,
  },
  inputWrapperError: {
    borderColor: '#EF4444',
    backgroundColor: '#FEF2F2',
  },
  fieldIcon: {
    marginRight: 10,
  },
  input: {
    flex: 1,
    fontFamily: 'Poppins_600SemiBold',
    fontSize: 15,
    color: '#0B132B',
    letterSpacing: 0.5,
  },
  errorRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 4,
    marginLeft: 2,
  },
  errorText: {
    fontFamily: 'Poppins_400Regular',
    fontSize: 12,
    color: '#EF4444',
  },
  helpBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 16,
    paddingVertical: 14,
    borderRadius: 16,
    marginTop: 20,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  helpText: {
    flex: 1,
    fontFamily: 'Poppins_400Regular',
    fontSize: 12,
    lineHeight: 18,
    color: '#6C7A9C',
  },
});