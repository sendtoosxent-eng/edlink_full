import React, { useState } from 'react';
import { Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import type { Role } from '../../types';
const LOGO_IMAGE = require('../../../assets/img/edlink-logo.png');

type RoleOption = {
  key: Role;
  label: string;
  description: string;
  iconName: React.ComponentProps<typeof Ionicons>['name'];
};

const ROLES: RoleOption[] = [
  {
    key: 'teacher',
    label: 'Teacher',
    description: 'Manage classes, record attendance, and grade student work.',
    iconName: 'school-outline',
  },
  {
    key: 'student',
    label: 'Student',
    description: 'Access timetable, complete assignments, and track grades.',
    iconName: 'person-outline',
  },
  {
    key: 'parent',
    label: 'Parent or Guardian',
    description: 'Monitor daily attendance, view report cards, and notices.',
    iconName: 'people-outline',
  },
];

export function RoleSelectorScreen({ onSelect }: { onSelect: (role: Role) => void }) {
  const [selected, setSelected] = useState<Role>();

  return (
    <SafeAreaView style={styles.screen}>
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.container}
      >
        {/* Top Header & Logo Area */}
        <View style={styles.header}>
          <Image
            accessibilityLabel="Edlink"
            source={LOGO_IMAGE}
            style={styles.logo}
            resizeMode="contain"
          />
          <Text style={styles.title}>How will you use Edlink?</Text>
          <Text style={styles.subtitle}>
            Choose your role to customize your experience.
          </Text>
        </View>

        {/* Centered Role Cards List */}
        <View style={styles.roleList}>
          {ROLES.map((item) => {
            const isActive = selected === item.key;

            return (
              <Pressable
                key={item.key}
                onPress={() => setSelected(item.key)}
                style={({ pressed }) => [
                  styles.card,
                  isActive && styles.cardActive,
                  pressed && styles.cardPressed,
                ]}
              >
                {/* Checkmark indicator badge */}
                <View style={[styles.badge, isActive && styles.badgeActive]}>
                  {isActive ? (
                    <Ionicons name="checkmark-circle" size={20} color="#0B132B" />
                  ) : (
                    <View style={styles.radioOutline} />
                  )}
                </View>

                {/* Role Icon */}
                <View style={[styles.iconWrapper, isActive && styles.iconWrapperActive]}>
                  <Ionicons
                    name={item.iconName}
                    size={28}
                    color={isActive ? '#0B132B' : '#FFFFFF'}
                  />
                </View>

                {/* Text Content */}
                <Text style={[styles.roleTitle, isActive && styles.roleTitleActive]}>
                  {item.label}
                </Text>
                <Text style={styles.roleDescription}>{item.description}</Text>
              </Pressable>
            );
          })}
        </View>

        {/* Action Button */}
        <View style={styles.footer}>
          <Pressable
            disabled={!selected}
            onPress={() => selected && onSelect(selected)}
            style={({ pressed }) => [
              styles.continueButton,
              !selected && styles.buttonDisabled,
              pressed && styles.buttonPressed,
            ]}
          >
            <Text style={styles.continueText}>Continue</Text>
            <Ionicons name="arrow-forward" size={18} color="#0B132B" />
          </Pressable>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  screen: {
    flex: 1,
    backgroundColor: '#F4F6FA',
  },
  container: {
    flexGrow: 1,
    justifyContent: 'flex-start',
    alignItems: 'center',
    paddingHorizontal: 24,
    paddingTop: 8,
    paddingBottom: 32,
  },
  header: {
    alignItems: 'center',
    marginBottom: 28,
    width: '100%',
  },
  logo: {
    width: 168,
    height: 118,
    marginBottom: 10,
  },
  title: {
    fontFamily: 'Poppins_700Bold',
    fontSize: 26,
    lineHeight: 34,
    color: '#0B132B',
    textAlign: 'center',
  },
  subtitle: {
    fontFamily: 'Poppins_400Regular',
    fontSize: 14,
    color: '#6C7A9C',
    textAlign: 'center',
    marginTop: 6,
  },
  roleList: {
    width: '100%',
    gap: 16,
  },
  card: {
    width: '100%',
    paddingVertical: 22,
    paddingHorizontal: 20,
    borderRadius: 24,
    backgroundColor: '#FFFFFF',
    borderWidth: 2,
    borderColor: 'transparent',
    alignItems: 'center',
    position: 'relative',
    shadowColor: '#0B132B',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.04,
    shadowRadius: 14,
    elevation: 2,
  },
  cardActive: {
    borderColor: '#efb000',
    backgroundColor: '#FFFFFF',
    shadowOpacity: 0.1,
    shadowRadius: 18,
    elevation: 4,
  },
  cardPressed: {
    transform: [{ scale: 0.985 }],
    opacity: 0.92,
  },
  badge: {
    position: 'absolute',
    top: 16,
    right: 16,
  },
  badgeActive: {},
  radioOutline: {
    width: 18,
    height: 18,
    borderRadius: 9,
    borderWidth: 2,
    borderColor: '#CBD5E1',
  },
  iconWrapper: {
    width: 60,
    height: 60,
    borderRadius: 50,
    backgroundColor: '#0B132B',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  iconWrapperActive: {
    backgroundColor: '#efb000',
  },
  roleTitle: {
    fontFamily: 'Poppins_700Bold',
    fontSize: 18,
    color: '#0B132B',
    textAlign: 'center',
  },
  roleTitleActive: {
    color: '#0B132B',
  },
  roleDescription: {
    fontFamily: 'Poppins_400Regular',
    fontSize: 13,
    lineHeight: 19,
    color: '#6C7A9C',
    textAlign: 'center',
    marginTop: 4,
    paddingHorizontal: 8,
  },
  footer: {
    width: '100%',
    marginTop: 28,
  },
  continueButton: {
    width: '100%',
    height: 56,
    borderRadius: 18,
    backgroundColor: '#efb000',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    shadowColor: '#0B132B',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 3,
  },
  buttonDisabled: {
    backgroundColor: '#E2E8F0',
    shadowOpacity: 0,
    elevation: 0,
  },
  buttonPressed: {
    opacity: 0.88,
    transform: [{ scale: 0.98 }],
  },
  continueText: {
    fontFamily: 'Poppins_700Bold',
    fontSize: 16,
    color: '#0B132B',
  },
});
